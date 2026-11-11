#!/usr/bin/env python3
"""Placeholder timing (no voice yet): spread a script over `total` seconds by spoken weight.
usage: estimate_timing.py <script.txt> <out.json> [total=89.4]"""
import json, re, sys
src, out = sys.argv[1:3]; total = float(sys.argv[3]) if len(sys.argv) > 3 else 89.4
L = [l.strip() for l in open(src, encoding='utf-8') if l.strip()]
def w(l):
    extra = 4 * len(re.findall(r'\[(?:long )?pause\]', l)) + 2 * len(re.findall(r'\[short pause\]', l))
    l = re.sub(r'\[[^\]]+\]', '', l)
    return len(re.sub(r'[\s\W_]', '', l)) + 2.5 * len(re.findall(r'[،,:؟?….]', l)) + extra
W = [w(l) for l in L]; gap = .35; r = (total - gap * len(L)) / sum(W); t = 0; lines = []
for i, l in enumerate(L):
    lines.append({'t0': round(t, 3), 't1': round(t + W[i] * r, 3), 'text': l}); t += W[i] * r + gap
json.dump({'duration': total, 'tempo': 1.0, 'placeholder': True, 'lines': lines}, open(out, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print(' '.join(f"{x['t0']:.1f}" for x in lines))
