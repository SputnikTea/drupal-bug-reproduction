# Bug reproduction for missbehavior of permission handling for image files on azure blob storage

## Setup

Prerequisites:
- Docker & docker-compose

How to start the reproduction environment:
- Clone this repository
- cd into the repository
- run command `./quick-local-setup.sh`
 
This will start the complete environment in docker-compose. 
- Drupal will be available on `http://localhost:30007` with the admin user `admin` and password `admin`
- GraphQL will be available on `http://localhost:30007/graphql` (no authentication required)

## Modules And Versions

```
"composer/installers": "^1.12",
"drupal/az_blob_fs": "^2.3",
"drupal/core-composer-scaffold": "^10.2.3",
"drupal/core-project-message": "^10.2.3",
"drupal/core-recommended": "^10.2.3",
"drupal/graphql": "^4.6",
"drupal/graphql_core_schema": "^1.0.2@beta",
"drush/drush": "^12.4.3"
```

## Settings
All users (including `Anonymous`) have been permitted to all possible actions / permissions, means all users (including `Anonymous`) are allowed to do anything.

## GraphQL Request
```
query files {
    entityQuery(
        entityType: NODE,
        revisions: ALL,
        filter: { conditions: [{field: "status", value: ["0", "1"]}]}) {
        __typename
        items {
            id
            ... on NodeDemo {
                vid
                nid
                status
                moderationState
                revisionUid {
                    id
                    label
                }
                body
                fieldImage {
                    entity {
                        filename
                        uri
                    }
                }
            }
        }
    }
}
```
You can run the request with (using curl)
```bash
./graphqlQuery.sh | jq
```

# Observed Behaviour
When executing the GraphQL we observed `"entity": null` even if there's an image present (database structure, Azure-Blob storage) and should have been delivered:
```
{
  "data": {
    "entityQuery": {
      "__typename": "EntityQueryResult",
      "items": [
        
        ...

        {
          "id": "2",
          "vid": 8,
          "nid": 2,
          "status": false,
          "moderationState": "draft",
          "revisionUid": {
            "id": "1",
            "label": "admin"
          },
          "body": null,
          "fieldImage": {
      -----> "entity": null <-----
          }
        },
        
        ...

      ]
    }
  }
}
```

# Expected Behaviour
```
{
  "data": {
    "entityQuery": {
      "__typename": "EntityQueryResult",
      "items": [
        
        ...

        "body": null,
        "fieldImage": {
       -----> "entity": { <-----
              "filename": "Preview.jpeg",
              "uri": "azblob:\/\/2024-04\/Preview.jpeg"
            }
        
        ...

      ]
    }
  }
}
```

# Analysis
## Where does this happen?
The GraphQL core schema checks the permissions before delivering the requested data (which is called by `graphql-core-schema/src/Wrappers/QueryConnection.php -> itmes() l.87`). 
The permission check is done with class `EntityAccessControlHandler` function `access`:  
It calls hooks `hook_entity_access` and `hook_<ENTITY-TYPE>_access` to decide whether an `Entity` is allowed to operation "view" (in terms of "access") or not, see Drupal/core/lib/Drupal/Core/Entity/EntityAccessControlHandler.php lines 96 - 104:
```
    // We grant access to the entity if both of these conditions are met:
    // - No modules say to deny access.
    // - At least one module says to grant access.
    $access = array_merge(
      $this->moduleHandler()->invokeAll('entity_access', [$entity, $operation, $account]),
      $this->moduleHandler()->invokeAll($entity->getEntityTypeId() . '_access', [$entity, $operation, $account])
    );

    $return = $this->processAccessHookResults($access);
```
## What happens (not)?
We observed, that:
* up to this code (line 99) `$entity`-objects always contained all data we expected - including the fileImage entity we missed in the GraphQL result
* neither a general module hook `_entity_access` nor `file_access` (for ENTITY-TYPE = "file") was called - but (for all `fileImage` entities) a hook `content_moderation_entity_access` was called
* all passed `$entity`-objects have been of type/class `Drupal\file\Entity\File` (independent if they are stored on the Azure-Blob storage or file system)  but none of the objects - as far as we could analyze - implemented the `EntityPublishedInterface`.

## Summary
Following the code in the called hook `content_moderation_entity_access` in Drupal/core/modules/content_moderation/content_moderation.module in line 220
```php
if ($operation === 'view') {
    $access_result = (($entity instanceof EntityPublishedInterface) && !$entity->isPublished())
      ? AccessResult::allowedIfHasPermission($account, 'view any unpublished content')
      : AccessResult::neutral();

    $access_result->addCacheableDependency($entity);
  }
```
we would expect `AccessResult::neutral();` for all entities that have been passed because:
* `$operation` was always `'view'`
* `$entity` is of type `Drupal\file\Entity\File` and thus does not implement `EntityPublishedInterface`

But surprisingly, the access right "view" was only denied for files that have the status "Draft" and are stored in an Azure blob storage. All other files ("published" or stored in the file system) could be accessed with "view" as expected (if permitted).
We suspect that another hook we did not observe was involved in the decision-making process.

## Unsecure/Temporary Workaround
We did not dive deeper. We assume, that other hooks are involved. Thus, our current workaround is a hook like this:
```
function <OUR_HOOK_MODULE>_entity_access(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view'
        && $entity instanceof Drupal\file\Entity\File
        && str_starts_with($entity->getFileUri(), "azblob:")) {
        return AccessResult::allowedIfHasPermission($account, 'view any unpublished content');
    }
    return AccessResult::neutral();
}
```