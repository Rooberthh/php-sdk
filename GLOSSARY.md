# PHP SDK

A framework for building API client libraries in PHP: it supplies the request, response and pagination machinery so that wrapping an HTTP API is mostly declaring its shape.

## Language

### People

**SDK Author**:
The developer who builds an SDK for one specific API on top of this framework.
_Avoid_: Developer, integrator, user, "you"

**Consumer**:
The developer whose application calls an SDK that an SDK Author has built.
_Avoid_: End user, caller, client

### Structure

**SDK**:
The library an SDK Author builds for one API and ships to Consumers.
_Avoid_: Using "SDK" for any single class within it

**Client**:
The entry point of an SDK: the one object a Consumer creates to talk to the API.
_Avoid_: SDK, connector, API

**Resource**:
A group of operations an SDK exposes for one kind of thing in the API, reached from the Client or, when nested, from another Resource. Operations that concern no particular kind of thing may sit on the Client directly.
_Avoid_: Endpoint, service, repository

**Transport**:
The component that actually sends HTTP requests on the Client's behalf.
_Avoid_: HTTP client, adapter

### Data

**Payload**:
The data an SDK sends to the API with a request. The outbound counterpart of a Data Object.
_Avoid_: Request DTO, input, body

**Data Object**:
A typed representation of something the API sent back.
_Avoid_: DTO, model, entity

**Envelope**:
The wrapper an API puts around the data it returns, often alongside metadata about it.
_Avoid_: Wrapper, container

**Filter**:
A criterion that narrows which records the API returns. Sorting, includes and field selection are not Filters.
_Avoid_: Query param, where clause, constraint
