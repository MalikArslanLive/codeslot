#!/usr/bin/env python3
"""HTTP source assertions against seeded, real WordPress URLs."""
import json, subprocess, urllib.request
fixtures=json.loads(subprocess.check_output(['docker','exec','codeslot-wp','wp','option','get','codeslot_fixtures','--format=json']))
checks=0
for key, fixture in fixtures.items():
    if key=='demo-a': continue  # ephemeral CPT registration is tested in integration, not persisted on HTTP requests
    body=urllib.request.urlopen(fixture['url']).read().decode()
    for global_marker in ['CS-GLOBAL-HEADER','CS-GLOBAL-FOOTER']:
        assert body.count(global_marker)==1,(key,global_marker,body.count(global_marker))
        checks+=1
    for other, expected in fixtures.items():
        if other=='demo-a': continue
        assert body.count(expected['marker'])==(1 if key==other else 0),(key,other)
        checks+=1
    assert 'Fatal error' not in body and 'Warning:' not in body,key
    print('PASS isolation:',key)
print('PASS:',checks,'source marker assertions')
