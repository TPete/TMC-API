# TMC API

Web-based media center. 

The API follows rest principles as well as JSON API.

Uses the [slim framework](https://www.slimframework.com/) version 3.5.

## Setup

tbd.

## Reference

`tmc.api` is used as the base url throughout this reference.

### API information

`tmc.api`

Return meta information about the API, for example the current version.

### Config API

Inspect and edit the API configuration.

#### Get config

Get the API config, i.e. database credentials, keys for the external APIs and the paths for the main categories.

GET `tmc.api/config/`

**Config Resource**

tbd.

#### Update config

Update the API config, i.e. database credentials, keys for the external APIs and the paths for the main categories.

POST `tmc.api/config/`

**Config Resource**

tbd.

#### Check config

(GET) `tmc.api/config/check/{type}/`

Check the api settings.

**Path Parameters**

type (string):  Which config option should be verified. Either `db` (database) or one of the main categories.

**Config check resource**

tbd

#### Setup Database

Setup the APIs database, that is, create the needed database tables.

(POST) `tmc.api/config/db/`


## Areas API

(GET) `tmc.api/areas/`

Get meta information about the areas.

## Series API

tbd

## Movies API 

### Get sub categories

(GET) `tmc.api/movies/`

Get sub categories of the movies area.

### Area resource
