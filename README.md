# paws

Fluffy Paws — the web application layer on top of the [Fluffy](../fluffy) framework. Treats
included ;)

Where Fluffy gives you the server, the ORM and the build, Paws gives you the things every web
application needs before it can do anything interesting:

- **Accounts and sessions** — registration, login, password reset, email confirmation, session
  limits and token rotation.
- **Permissions** — a bitmask of capabilities and roles on the user, checked per controller method.
- **Admin UI (Pupils)** — a ready admin area: dashboard, CRUD scaffolding, data tables, forms and
  the layout around them.
- **Shared components (SharedPaws)** — the models and Viewi components reused by both the admin and
  the application side.

## Packages

| Directory | Contains |
|---|---|
| `FluffyPaws` | Server-side application layer: auth, permissions, settings, mail, controllers |
| `Pupils` | The admin interface — layouts, CRUD pages, data tables |
| `SharedPaws` | Models and Viewi components shared between server and browser |

## Status

Used in production — it is the application layer behind [urlicer.com](https://urlicer.com).
The same caveat as Fluffy applies: public APIs still change between versions, and the docs are
thinner than the code. See the Fluffy README for the fuller picture.
