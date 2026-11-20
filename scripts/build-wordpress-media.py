#!/usr/bin/env python3
"""Bundle separate WordPress.org media; never add demo data to the plugin ZIP."""
from pathlib import Path
import hashlib,re,shutil,struct,zipfile
root=Path(__file__).resolve().parent.parent
media=root/'wordpress-org'; packages=root/'packages';packages.mkdir(exist_ok=True)
expected={**{f'gallery/screenshot-{n}.png':None for n in range(1,9)},'icons/icon-128x128.png':(128,128),'icons/icon-256x256.png':(256,256),'banners/banner-772x250.png':(772,250),'banners/banner-1544x500.png':(1544,500)}
actual={str(p.relative_to(media)) for d in ['gallery','icons','banners'] for p in (media/d).glob('*') if p.is_file()}
assert actual==set(expected),(actual,set(expected))
readme=(root/'readme.txt').read_text(); captions=readme.split('== Screenshots ==',1)[1].split('\n== ',1)[0]
assert [int(n) for n in re.findall(r'^(\d+)\. ',captions,re.M)]==list(range(1,9))
manifest=[]
for name,size in expected.items():
 data=(media/name).read_bytes();assert data[:8]==b'\x89PNG\r\n\x1a\n'
 width,height=struct.unpack('>II',data[16:24]);assert len(data)<10*1024*1024
 assert (width,height)==size if size else width==1600 and 900<=height<=2000
 manifest.append(hashlib.sha256(data).hexdigest()+'  '+name)
(media/'ASSET-SHA256SUMS').write_text('\n'.join(manifest)+'\n')
output=packages/'codeslot-wordpress-org-media-1.0.0.zip'
with zipfile.ZipFile(output,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as archive:
 files={name:(media/name).read_bytes() for name in sorted(expected)}
 files.update({'README-SVN.md':(media/'README.md').read_bytes(),'readme.txt':(root/'readme.txt').read_bytes(),'ASSET-SHA256SUMS':(media/'ASSET-SHA256SUMS').read_bytes()})
 for name,data in files.items():
  entry=zipfile.ZipInfo(name,(2026,1,1,0,0,0));entry.compress_type=zipfile.ZIP_DEFLATED;entry.external_attr=0o100644<<16;archive.writestr(entry,data)
with zipfile.ZipFile(output) as archive:assert archive.testzip() is None
shutil.copyfile(root/'dist/codeslot-1.0.0.zip',packages/'codeslot-1.0.0.zip')
shutil.copyfile(root/'readme.txt',packages/'readme.txt')
names=['codeslot-1.0.0.zip','codeslot-wordpress-org-media-1.0.0.zip','readme.txt']
(packages/'SHA256SUMS').write_text(''.join(hashlib.sha256((packages/n).read_bytes()).hexdigest()+'  '+n+'\n' for n in names))
print('Verified 8 screenshots, 2 icon sizes, 2 banner sizes and matching readme captions.')
print(output)
