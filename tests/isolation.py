#!/usr/bin/env python3
"""HTTP source assertions against seeded, real WordPress URLs."""
import json, subprocess, urllib.request, os, shlex
fixtures=json.loads(subprocess.check_output(shlex.split(os.environ.get('CS_WP_CLI', 'docker compose run --rm cli'))+['option','get','codeslot_fixtures','--format=json']))
checks=0
for key, fixture in fixtures.items():
    body=urllib.request.urlopen(fixture['url']).read().decode()
    for global_marker in ['CS-GLOBAL-HEADER','CS-GLOBAL-FOOTER']:
        assert body.count(global_marker)==1,(key,global_marker,body.count(global_marker))
        checks+=1
    for other, expected in fixtures.items():
        assert body.count(expected['marker'])==(1 if key==other and key not in ['post-b','product-b'] else 0),(key,other)
        checks+=1
    assert 'Fatal error' not in body and 'Warning:' not in body,key
    print('PASS isolation:',key)
print('PASS:',checks,'source marker assertions')
