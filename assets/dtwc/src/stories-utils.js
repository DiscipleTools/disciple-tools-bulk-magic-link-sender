import { html } from 'lit';

export const LocaleDecorator = (story, context) =>
  html`<div
    lang="${context?.args?.lang || 'en'}"
    dir="${context?.args?.dir || 'ltr'}"
  >
    ${story()}
  </div>`;

export const FormDecorator = story => html`<form onsubmit="onFormSubmit(event)">
    ${story()}

    <button type="submit">Submit</button>

    <pre><output></output></pre>
  </form>
  <script>
    function onFormSubmit(event) {
      if (event) {
        event.preventDefault();
      }

      const output = document.querySelector('output');

      const form = event.target;

      /** Get all of the form data */
      const formData = new FormData(form);
      const data = {};
      formData.forEach((value, key) => (data[key] = value));
      output.innerHTML = JSON.stringify(data, null, 2);
    }
  </script> `;
