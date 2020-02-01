# TODO

## Application

* Readme -> **WIP**
* Separation of concerns -> **Done**
    * Routing -> **Done**
    * Controller -> **Done**
    * Services -> **Done**
* Dependency Injection -> **Done**
* Code Style -> **WIP**
    * Interfaces -> **WIP**
    * Models -> **WIP**
* JSON API -> **WIP**
* Url structure-> **Done**
    * / (api meta)
    * /config/
    * /areas/ (movies, series, etc.) (list of AreaModels)
    * /areas/movies/ (movies area overview) (AreaModel)
    * /areas/movies/categories/ (movies area categories, e.g. english, german) (list of CategoryModels)
    * /areas/movies/categories/{category}/ (category index) (list of MovieModels)
    * /areas/movies/categories/{category}/entry/{movie}/ (get movie details/update movie details) (MovieModel)
    * /areas/movies/categories/{category}/genres/ (genre list for category) (list of GenreModels)
    * /areas/movies/categories/{category}/collections/ (collection list for category) (list of CollectionModels)
    * /areas/movies/maintenance/ (update movie data) (MaintenanceModel)
    * /areas/series/ (series area overview) (AreaModel)
    * /areas/series/categories/ (series area categories, e.g. english, german) (list of CategoryModels)
    * /areas/series/categories/{category}/ (category index) (list of SeriesModels)
    * /areas/series/categories/{category}/entry/{series}/ (get series details/update series details) (SeriesModel)
    * /areas/series/categories/{category}/entry/{series}/episodes/{episode}/ (get episode details) (EpisodeModel)
    * /areas/series/maintenance/ (update series data) (MaintenanceModel)
* Url structure 2.0
    * There ist no episode overview (/areas/series/categories/{category}/entry/{show}/episodes/). Add it? How are episodes fetched currently?
    * Maintenance actions update all categories of the area. Maybe move it down to category level (the frontend would have to call each category)?
    * Where to put actions to query the external apis (e.g. /movies/lookup/{externalId}/)?
* Tests
* PHP 7.*
* Logging
* Deployment process
* Dependencies
    * replace getid3 or perhaps there is an updated version?


## Series

### SeriesService

* Sort out categories, category names, path, alias in SeriesService
* Sort out naming (folder vs. series vs. title vs. what-not) in SeriesService

### ShowStoreDB

* Rename to SeriesStore ?
* use Model for series data
* remove unused functions
* add interface

### TTVDBWrapper

* Rename to tvDbApi ?
* add interface
* Upgrade to API version 3 ?

## Movies

