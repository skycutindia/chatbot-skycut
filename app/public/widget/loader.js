/**

 * AI Chatbot Hub Pro — Widget loader (aichatbot global)

 *

 * Optional attribute on this script tag:

 *   data-cdn-base="https://cdn.example.com/widget/" — base URL for chatbot.js (trailing slash optional)

 */

(function () {

  'use strict';



  const queue = window.aichatbot && window.aichatbot.q ? window.aichatbot.q : [];

  const api = function () {

    (api.q = api.q || []).push(arguments);

  };

  api.q = queue;

  window.aichatbot = api;



  function assetBase() {

    const loader = document.currentScript;

    const cdn = loader && loader.getAttribute('data-cdn-base');

    if (cdn) {

      return cdn.endsWith('/') ? cdn : cdn + '/';

    }

    return new URL('./', loader.src).href;

  }



  function inject(token) {

    if (document.querySelector('script[data-bot-token="' + token + '"]')) return;

    const s = document.createElement('script');

    s.src = assetBase() + 'chatbot.js';

    s.setAttribute('data-bot-token', token);

    s.async = true;

    document.head.appendChild(s);

  }



  function process(args) {

    const cmd = args[0];

    if (cmd === 'init' && args[1] && args[1].website_key) {

      inject(args[1].website_key);

    }

  }



  queue.forEach(process);

  api.push = function () {

    process(arguments);

    return api.q.length;

  };

})();

