<?php

//searches through the array below to find the closes number
function convert_z_score_to_percentile($z_score)
{
	global $table_z_scores, $table_percentiles;
	
//	echo "<br>score: " . $z_score;

	
	$cur = -3;
	$last = 0;
	
	for ($i = 0; $i < count($table_z_scores); $i++)
	{
		$cur = $table_z_scores[$i];

		if ($cur > $z_score)
		{
			break;
		}
		else
		{
			$last = $i;
		}
	}
	
//	echo "last: " . $last;
	
	return($table_percentiles[$last]);	
}

$table_z_scores = array(
-2.326	,
-2.054	,
-1.881	,
-1.751	,
-1.645	,
-1.555	,
-1.476	,
-1.405	,
-1.341	,
-1.282	,
-1.227	,
-1.175	,
-1.126	,
-1.08	,
-1.036	,
-0.994	,
-0.954	,
-0.915	,
-0.878	,
-0.842	,
-0.806	,
-0.772	,
-0.739	,
-0.706	,
-0.674	,
-0.643	,
-0.613	,
-0.583	,
-0.553	,
-0.524	,
-0.496	,
-0.468	,
-0.44	,
-0.412	,
-0.385	,
-0.358	,
-0.332	,
-0.305	,
-0.279	,
-0.253	,
-0.228	,
-0.202	,
-0.176	,
-0.151	,
-0.126	,
-0.1	,
-0.075	,
-0.05	,
-0.025	,
0	,
0.025	,
0.05	,
0.075	,
0.1	,
0.126	,
0.151	,
0.176	,
0.202	,
0.228	,
0.253	,
0.279	,
0.305	,
0.332	,
0.358	,
0.385	,
0.412	,
0.44	,
0.468	,
0.496	,
0.524	,
0.553	,
0.583	,
0.613	,
0.643	,
0.674	,
0.706	,
0.739	,
0.772	,
0.806	,
0.842	,
0.878	,
0.915	,
0.954	,
0.994	,
1.036	,
1.08	,
1.126	,
1.175	,
1.227	,
1.282	,
1.341	,
1.405	,
1.476	,
1.555	,
1.645	,
1.751	,
1.881	,
2.054	,
2.326	);


$table_percentiles = array(
1	,
2	,
3	,
4	,
5	,
6	,
7	,
8	,
9	,
10	,
11	,
12	,
13	,
14	,
15	,
16	,
17	,
18	,
19	,
20	,
21	,
22	,
23	,
24	,
25	,
26	,
27	,
28	,
29	,
30	,
31	,
32	,
33	,
34	,
35	,
36	,
37	,
38	,
39	,
40	,
41	,
42	,
43	,
44	,
45	,
46	,
47	,
48	,
49	,
50	,
51	,
52	,
53	,
54	,
55	,
56	,
57	,
58	,
59	,
60	,
61	,
62	,
63	,
64	,
65	,
66	,
67	,
68	,
69	,
70	,
71	,
72	,
73	,
74	,
75	,
76	,
77	,
78	,
79	,
80	,
81	,
82	,
83	,
84	,
85	,
86	,
87	,
88	,
89	,
90	,
91	,
92	,
93	,
94	,
95	,
96	,
97	,
98	,
99	);