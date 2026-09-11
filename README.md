# Loan Application Intake & Verification

Accepts loan applications and verifies them using an external credit-bureau service.

The external service may be slow or temporarily unavailable, so the application is designed to handle timeouts and errors without losing requests.

## How it works

When an application is created, it is saved to the database together with a verification message in the same transaction (Transactional Outbox pattern).
The process has two steps: a background worker reads the message from Postgres and sends it to RabbitMQ.
Another worker receives it from RabbitMQ and calls the external vendor.

This way, creating an application does not depend on RabbitMQ or the vendor being available.
If something is temporarily unavailable, the message is retried later. If all retries fail,
it is moved to a failed-message queue instead of being lost.

## Requirements

- Docker
- Docker Compose

## Quick start

```bash
docker compose run --rm php composer install
docker compose up -d --build
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

API: [http://localhost:8080](http://localhost:8080).

Interactive docs: [http://localhost:8080/api/doc/](http://localhost:8080/api/doc/).

RabbitMQ UI: [http://localhost:15672](http://localhost:15672) (guest/guest).

The workers start automatically with Docker Compose. No additional setup is required.

## API

| Method | Path                 | Description               |
|--------|----------------------|---------------------------|
| POST   | `/applications`      | Create an application     |
| GET    | `/applications/{id}` | Get an application        |
| GET    | `/applications`      | List applications         |

```bash
curl -X POST http://localhost:8080/applications \
  -H "Content-Type: application/json" \
  -d '{"personalCode":"010199-12345","amount":"1000.00","term":24,"currency":"EUR"}'
```

- `personalCode` is checked structurally as a pre-2017 Latvian personal code (`DDMMYY-CCCCC`) — format only, no checksum
- `amount` is `100.00`–`5000.00`
- `term` is `10`–`30`
- `currency` is always `EUR`

Full schemas are in the Swagger UI.

## Simulating the vendor

There is no real credit bureau in the local environment. Instead, the application uses a mock `/v1/verifications` endpoint that returns predefined responses based on the `personalCode`.

You can send:

| personalCode   | Response                                                              |
|----------------|-----------------------------------------------------------------------|
| `010101-00001` | approve                                                               |
| `010101-00002` | reject                                                                |
| `010101-00429` | 429, retried                                                          |
| `010101-00500` | 500, retried                                                          |
| `010101-00400` | 400, not retried                                                      |
| `010101-00408` | never responds — client times out, retried                            |
| `010101-00050` | random outcome each call — good for watching a retry actually recover |
| anything else  | approve                                                               |

## Testing

```bash
docker compose exec php vendor/bin/phpstan analyse
docker compose exec php vendor/bin/phpunit
```

## Configuration

Main env vars, all set already for local dev:

- `POSTGRES_DB` / `POSTGRES_USER` / `POSTGRES_PASSWORD` — also read directly by `docker-compose.yml`
- `CREDIT_BUREAU_BASE_URL` — the fake vendor locally, the real one in prod
- `RABBITMQ_VERIFICATION_DSN` — AMQP DSN for the verification queue

## Design notes

**Safe message redelivery.** 
Application status transitions are handled by the Application entity (`approve()`, `reject()`, `failVerification()`).
An application cannot be resolved twice, and status updates use a conditional update (`UPDATE ... WHERE status = ?`).
If a message is delivered again after the application has already been resolved, the vendor call is skipped.

**Retry policy.**
This applies specifically to the RabbitMQ verification queue. Only temporary errors (`429`, `5xx`, and timeouts) are retried.
A `400 Bad Request` is not retried because sending the same invalid request again would produce the same result.

**Why failed messages are stored in Postgres.**
Failed messages are stored separately from RabbitMQ so they remain available for inspection and recovery independently of the broker.
Using Postgres also keeps both failed transports in one place and makes them easy to manage using standard database tools.

## Scaling the workers

Both workers can be scaled up with Docker Compose, no code changes needed:

```bash
docker compose up -d --scale worker-outbox=5 --scale worker-verification=5
```

## Given more time

- **Failed message handling.**
Add monitoring and notifications for failed messages, with an option to review and retry them manually.
- **Vendor response caching.**
Cache recent `REJECT` results to avoid unnecessary vendor calls. `APPROVE` results should not be cached because the borrower's situation may change.
- **Separate verification from approval.**
A vendor `APPROVE` should not automatically approve the application. An intermediate status such as `VerificationApproved` would allow additional business rules to be applied later.
