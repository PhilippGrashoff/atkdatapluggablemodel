# atkdatapluggablemodel


This package can be used to have pluggable models for atk4/data. The idea is to make different PHP execution logic available to different model entities.
One example from one of my projects:
Users can create connectors to third party services. The actual logic of each connector and both their fields widely differ.
For example, there is an SMTP connector with typical SMTP fields (server, port, username, password), but also connectors to various REST APIs which typically have URL and API key fields.

With this package, implementing this is easily possible:
Models using PluggableModelTrait are meant to store data:
- which implementation (in the above example connector) is used
- the additional fields of the implementation and their values. These fields are defined through classes extending `BaseImplementation::getFieldDefinitions()`
As all additional fields are stored in a JSON field, the same database setup can be used for all implementations, no matter how many additional fields each implementation needs.

### ContainsMany
Models using PluggableModelTrait can also contain many instances of another model. This is sometimes needed for very configurable implementations.
In the current implementation, there are some things to know:
- if one or many containsMany relations should be used, then the database needs to have a column for each containsMany relation.
- if a Model using PluggableModelTrait containsMany other models, then each entity always has to be loaded twice if the Implementation defines containsMany relations. This is because when loading the model initially, it is unknown which class the containsMany fields represent. 
- This is not an issue with a single Entity, but when iterating over a Model using PluggableModelTrait, then `addDynamicFields` should be set to false, e.g. when displaying all entities in a UI grid.