let localConfig = require("./backstop-local.js");
let testUrl = localConfig.dev_domain;
let refUrl = localConfig.prod_domain;
console.log(testUrl);
console.log(refUrl);

// Defaults stop you from having to repeat yourself a lot.
const defaultScenario = {
  "cookiePath": "backstop_data/engine_scripts/cookies.json",
  "delay": 1000,
  "removeSelectors": ["#onetrust-consent-sdk", ".geolocation-map-wrapper", "#CybotCookiebotDialog", ".anrt-gdpr-floating-cookie"],
  "misMatchThreshold": 5,
};

// Define individual scenarios here. First one is only verbose to show
// some of the options.
// This is all you actually need for a scenario: a label and a path.
// {
//    "label": "Contact",
//    "url": testUrl + "/contact-0",
//    "referenceUrl": refUrl + "/contact-0"
//  },
const scenarios = [
  {
    "label": "Home",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl,
    "referenceUrl": refUrl,
    "readyEvent": "",
    "readySelector": "",
    "delay": 0,
    "hideSelectors": [],
    "removeSelectors": ["#onetrust-consent-sdk", ".geolocation-map-wrapper", "#CybotCookiebotDialog", ".anrt-gdpr-floating-cookie"],
    "hoverSelector": "",
    "clickSelector": "",
    "postInteractionWait": "",
    "selectors": [],
    "selectorExpansion": true,
    "misMatchThreshold": 0.1,
    "requireSameDimensions": true
  },
  {
    "label": "About",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl + "/about-love-essex",
    "referenceUrl": refUrl + "/about-love-essex",
  },
  {
    "label": "Find a centre",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl + "/find-recycling-centre",
    "referenceUrl": refUrl + "/find-recycling-centre",
  },
  {
    "label": "News",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl + "/news",
    "referenceUrl": refUrl + "/news",
  },
  {
    "label": "Ideas",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl + "/ideas",
    "referenceUrl": refUrl + "/ideas",
  },
  {
    "label": "News item",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl + "/news/2024/businesses-invited-learn-about-draft-waste-strategy",
    "referenceUrl": refUrl + "/news/2024/businesses-invited-learn-about-draft-waste-strategy",
  },
  {
    "label": "Prevent",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl + "/ideas/prevent",
    "referenceUrl": refUrl + "/ideas/prevent",
  },
  {
    "label": "Braintree",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl + "/find-recycling-centre/braintree-recycling-centre",
    "referenceUrl": refUrl + "/find-recycling-centre/braintree-recycling-centre",
  },
  {
    "label": "Results",
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "url": testUrl + "/disposal-options?item=Painted+wood",
    "referenceUrl": refUrl + "/disposal-options?item=Painted+wood",
  }
];

// BackstopJS configuration
module.exports = {
  "id": "backstop_default",
  "viewports": [
    {
      "label": "phone",
      "width": 320,
      "height": 480
    },
    {
      "label": "tablet",
      "width": 1024,
      "height": 768
    },
    {
      "label": "desktop",
      "width": 1200,
      "height": 900
    }
  ],
  "onBeforeScript": "puppet/onBefore.js",
  "onReadyScript": "puppet/onReady.js",
  "scenarios": scenarios.map(scenario => ({
    ...defaultScenario,
    ...scenario,
  })),
  "paths": {
    "bitmaps_reference": "backstop_data/bitmaps_reference",
    "bitmaps_test": "backstop_data/bitmaps_test",
    "engine_scripts": "backstop_data/engine_scripts",
    "html_report": "backstop_data/html_report",
    "ci_report": "backstop_data/ci_report"
  },
  "report": ["browser"],
  "engine": "puppeteer",
  "engineOptions": {
    "args": ["--no-sandbox"]
  },
  "asyncCaptureLimit": 5,
  "asyncCompareLimit": 50,
  "debug": false,
  "debugWindow": false
}
