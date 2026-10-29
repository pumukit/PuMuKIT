# End-to-end tests

Browser tests written with [Playwright](https://playwright.dev) that go through the back-office and the
portal the way a person does: log in, create series, upload media, wait for the encoder, publish and
play in the portal.

## Running them

```bash
make test-e2e                      # whole suite
make test-e2e ARGS="video"         # only the files that match
make test-e2e ARGS="--debug"       # any other Playwright option
make test-e2e-down                 # remove the stack and its data
```

`make test-e2e` starts an isolated stack (`pumukit-e2e`) built from `docker-compose.yml` and
`docker-compose.e2e.yml`, with its own database, storage, uploads and `var/`, and no ports on the
host, so it can run next to the development stack. The stack stays up between runs; every test
creates its own data with a unique title, and `make test-e2e-down` throws everything away.

The tests run inside the official Playwright image, against `https://proxy`. They can also be
pointed at any other installation with `BASE_URL`, `E2E_ADMIN_USER` and `E2E_ADMIN_PASS`.

After a run, `playwright-report/` has the HTML report and `test-results/` the trace, video and
screenshot of every failed test (`npx playwright show-trace <trace.zip>`).

## Layout

- `playwright.config.js`: the `setup` project waits for the application and logs in as
  administrator once; the `chromium` project reuses that session.
- `support/fixtures.js`: the `test` to import in every spec. It hides the Symfony debug toolbar and
  gives `uniqueTitle()`.
- `support/admin.js`: back-office steps shared by the specs (create a series, upload a file, open a
  multimedia object, wait for a track).
- `tests/*.spec.js`: one file per area.
- Media files come from `tests/files/` (`pumukit.mp4`, `pumukit.mp3`, `pumukit.png`, `pumukit.pdf`).

## Coverage of the base application

| Area | What is checked | Status |
|---|---|---|
| Login | Anonymous users go to the login page, wrong credentials are rejected, log in and out | Done |
| Video | Create a series, upload a video, master copy job, edit metadata, publish in WebTV, broadcastable transcoding, portal page, search and playback | Done |
| Audio, image, document | Upload each type and see it processed and published | Pending |
| External | Create a multimedia object from a link and see it in the portal | Pending |
| Publication | Hidden and blocked objects are not listed in the portal, magic URL | Pending |
| Live | Create a channel, create an event with a session, countdown on the event page | Pending |
| People | Create a person and add them to a multimedia object with a role | Pending |
| Tags | Create a tag, add it to a multimedia object, see it in the portal | Pending |
| Places | Create a place and a precinct | Pending |
| Users and groups | Create a user and a group, add the user to the group | Pending |
| Permissions | Permission profiles and roles; a personal-scope user only sees their own content | Pending |
| UNESCO and playlists | Catalogue and playlist management | Pending |
| Tools | Series style, head and tail, slider | Pending |
| Dashboard and statistics | Panels load with data for each scope | Pending |
| Portal | Home, latest uploads, full catalogue, events, series page, sitemap | Pending |
