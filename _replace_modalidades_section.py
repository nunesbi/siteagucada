import re
from pathlib import Path
p = Path('pages/modalidades.html')
s = p.read_text(encoding='utf-8')
pattern = re.compile(r'(<section[^>]*data-loc="client/src/pages/Modalidades\.tsx:91"[^>]*>)(.*?)(</section><section[^>]*data-loc="client/src/pages/Modalidades\.tsx:106")', re.S)
m = pattern.search(s)
if not m:
    raise SystemExit('Section not found')
new_inner = Path('_new_inner.html').read_text(encoding='utf-8').strip('\n')
new_s = s[:m.start(2)] + '\n' + new_inner + '\n' + s[m.start(3):]
p.write_text(new_s, encoding='utf-8')
print('updated', p, 'len', len(new_s))
