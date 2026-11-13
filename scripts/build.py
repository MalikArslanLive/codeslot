#!/usr/bin/env python3
"""Deterministic allowlisted production archive. No development files."""
from pathlib import Path
import zipfile, hashlib
root=Path(__file__).resolve().parent.parent
dest=root/'dist'; dest.mkdir(exist_ok=True)
paths=[root/n for n in ['codeslot.php','uninstall.php','readme.txt','LICENSE']]
for d in ['includes','assets','templates']:
    paths+=sorted(p for p in (root/d).rglob('*') if p.is_file())
out=dest/'codeslot-1.0.0.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
    for p in paths:
        info=zipfile.ZipInfo('codeslot/'+str(p.relative_to(root)),(2026,1,1,0,0,0))
        info.compress_type=zipfile.ZIP_DEFLATED
        info.external_attr=0o100644 << 16
        z.writestr(info,p.read_bytes())
with zipfile.ZipFile(out) as z:
    assert z.testzip() is None
    assert all(n.startswith('codeslot/') for n in z.namelist())
    assert 'codeslot/codeslot.php' in z.namelist()
checksum=hashlib.sha256(out.read_bytes()).hexdigest()
(dest/'SHA256SUMS').write_text(checksum+'  '+out.name+'\n')
print(out,checksum)
