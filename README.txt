A/B TESTING FOR PHP USING REDIS

To set up this project:

1. Start up redis. Specify the host name and db number in config/configure.php
2. Define things to measure in config/metrics.php following the declaration pattern in the file's example
3. Define the tests you'd like to perform in config/tests.php following the pattern there. Specify a metric for each test as shown in the example
4. include core.php in your code.
5. make sure to set ab_participant_specify_id("a_unique_id_for_this_user") at least once
6. for every metric, call: ab_track("name_of_your_metric");
7. every time you need a choice, call: ab_test("name_of_your_ab_test"); and it will return a string represing the alternative to use

that is all.