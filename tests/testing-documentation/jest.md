jest is a testing framework for javascript.

in tests/js/flux.test.js

we test the flux.js file.

in flux-js.html.twig, we import flux.js and the css using 

    {{ encore_entry_link_tags('flux') }}


twice, one for the css and one for the js.

we use yarn test to run the tests.

in tests/js/flux.test.js, we have the following tests:

with yarn test --watch, it reload the tests when we make changes, which is great for tdd.


the current myddleware configuration is incomatible with our jest setup, so once we finish our tests, we need to go back to original configuration.