#!/usr/bin/env python3
"""Deterministic allowlisted production archive. No development files."""
from pathlib import Path
import zipfile, hashlib, re
root=Path(__file__).resolve().parent.parent
slug='codeslot-header-body-footer-code-snippet-manager'
header=(root/'codeslot.php').read_text()
assert re.search(r'Text Domain:\s*(\S+)',header).group(1)==slug, 'Text Domain must match the installation directory'
dest=root/'dist'; dest.mkdir(exist_ok=True)
paths=[root/n for n in ['codeslot.php','uninstall.php','readme.txt','LICENSE']]
for d in ['includes','assets','templates']:
    paths+=sorted(p for p in (root/d).rglob('*') if p.is_file())
out=dest/'codeslot-1.0.0.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
    for p in paths:
        info=zipfile.ZipInfo(slug+'/'+str(p.relative_to(root)),(2026,1,1,0,0,0))
        info.compress_type=zipfile.ZIP_DEFLATED
        info.external_attr=0o100644 << 16
        z.writestr(info,p.read_bytes())
with zipfile.ZipFile(out) as z:
    assert z.testzip() is None
    assert all(n.startswith(slug+'/') for n in z.namelist())
    assert slug+'/codeslot.php' in z.namelist()
checksum=hashlib.sha256(out.read_bytes()).hexdigest()
(dest/'SHA256SUMS').write_text(checksum+'  '+out.name+'\n')
print(out,checksum)
