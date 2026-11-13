#!/usr/bin/env python3
"""Verify identity, SAPI and real conditional/shortcode HTTP output."""
import json, os, subprocess, shlex, urllib.request
base = os.environ['CS_SERVER_URL']
def get(path):
    response = urllib.request.urlopen(base + path, timeout=30)
    assert response.geturl().startswith(base + '/'), response.geturl()
    return response.read().decode()
identity = json.loads(get('/codeslot-server-probe.php'))
assert identity['sapi'] == os.environ['CS_EXPECT_SAPI'], identity
assert os.environ['CS_EXPECT_SERVER'].lower() in identity['server'].lower(), identity
print(json.dumps(identity, sort_keys=True))
fixtures = json.loads(subprocess.check_output(shlex.split(os.environ.get('CS_WP_CLI', 'docker compose run --rm cli')) + ['option','get','codeslot_fixtures','--format=json']))
for key, fixture in fixtures.items():
    from urllib.parse import urlsplit
    url = urlsplit(fixture['url'])
    body = get(url.path + ('?' + url.query if url.query else ''))
    for marker in ['CONDITIONAL','SHORTCODE','EXCLUDED']:
        expected = (key != 'page-a') if marker == 'EXCLUDED' else (key == 'page-a')
        assert body.count('CS-SERVER-' + marker) == int(expected), (key, marker)
print('PASS: server identity/SAPI and 27 conditions, exclusions and shortcode assertions')
