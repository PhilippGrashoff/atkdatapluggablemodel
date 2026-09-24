# atkdatapluggablemodel

This package can be used to have pluggable models for atk4/data. The idea is to make 1) different PHP execution logic and 2) different additional fields available to different model entities.

## real world example: Connectors to third-party services
In one of my projects, users can create new connector instances to third-party services themselves. The actual logic of the different available connectors and the data they store widely differ.
For example, there is an SMTP connector with typical SMTP fields (server, port, username, password), but also connectors to various REST APIs which typically have an API key field.

### implementation using this library
- There are Implementations for SmtpConnector, SomeRestConnector, SomeOtherRestConnector etc. They extend `BaseImplementation` and carry the actual execution logic (connecting to an SMTP server, "talking" to a specific REST API etc.).
- These Implementations define the additional fields they need in `getFieldDefinitions()`.
- A generic Connector model uses `PluggableModelTrait`. The model itself is very simple and only contains an `implementation_class` field.
- As soon as `implementation_class` for a Connector entity is selected and saved, the additional fields of the implementation are automatically added to the Connector model after the entity is loaded.
- All additional fields are stored in a single JSON field, meaning no Database altering is needed when you add a new implementation/extend an existing one.
- as the connectors store credentials which should not be stored plain in the database, you can define for each implementation which fields should be stored encrypted. The actual encryption logic is done in the Connector model - you just need to pass a key.
See the tests and the simple test implementations on how to use this library.

## ContainsMany
Models using PluggableModelTrait can also contain many instances of another model. This is sometimes needed if an implementation needs enhanced configuration which cannot be created solely by additional fields.
In the current implementation, there are some things to know:
- if one or many containsMany relations should be used, then the database needs to have a column for each containsMany relation.
- if a Model using PluggableModelTrait containsMany other models, then each entity always has to be loaded twice if the Implementation defines containsMany relations. This is because when loading the model initially, it is unknown which class the containsMany fields represent. 
- This is not an issue with a single Entity, but when iterating over a Model using PluggableModelTrait, then `addFieldsFromImplementation` should be set to false, e.g. when displaying all entities in a UI grid.