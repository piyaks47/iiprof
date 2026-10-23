# shared/ — platform-agnostic code

Everything in this folder must run in **both** the browser and React Native:

- no DOM APIs (`window`, `document`, `localStorage`), no `react-dom`, no CSS imports
- only `react`, `@tanstack/react-query` and standard `fetch`

When the React Native app is created, move this folder into a shared package
(e.g. `packages/shared`) or copy it as-is, and call `configureApi({ baseUrl: 'https://piya.karmickdev.com/api/v1' })`
at app start-up. Screens in `src/web/` are the only part that needs a native rewrite.
