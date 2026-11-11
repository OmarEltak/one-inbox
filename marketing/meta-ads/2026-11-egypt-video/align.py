#!/usr/bin/env python3
"""Align script lines to a TTS voiceover without a speech model.

Every line break in our scripts is a sentence end, which the TTS voices with a
pause. We detect pauses with ffmpeg silencedetect, then pick N-1 of them as line
boundaries with a DP that minimises the squared error between each line's
duration and (its character count x the global speaking rate), with a bonus for
longer pauses. Output: timing-<name>.json consumed by engine/ad.html.

usage: align.py <script.txt> <audio.mp3> <out.json> [tempo]
  tempo: the atempo factor the final audio is sped up by (times are divided by it)
"""
import json, re, subprocess, sys

script, audio, out = sys.argv[1:4]
tempo = float(sys.argv[4]) if len(sys.argv) > 4 else 1.0

lines = [l.strip() for l in open(script, encoding='utf-8') if l.strip()]
def weight(l):
    l = re.sub(r'\[[^\]]+\]', '', l)                       # audio tags
    letters = len(re.sub(r'[\s\W_]', '', l))
    pauses = len(re.findall(r'[،,:؟?…]', l)) * 2.5         # inner pauses take time too
    return letters + pauses
W = [weight(l) for l in lines]

dur = float(subprocess.check_output(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', audio]))
log = subprocess.run(['ffmpeg', '-hide_banner', '-i', audio, '-af', 'silencedetect=noise=-30dB:d=0.18', '-f', 'null', '-'],
                     capture_output=True, text=True).stderr
starts = [float(x) for x in re.findall(r'silence_start: ([0-9.]+)', log)]
ends = [float(x) for x in re.findall(r'silence_end: ([0-9.]+)', log)]
sil = [(s, e) for s, e in zip(starts, ends) if s > 0.5 and e < dur - 0.3]

# speech onset / offset
t_on = ends[0] if starts and starts[0] < 0.05 else 0.0
t_off = starts[-1] if ends and ends[-1] > dur - 0.05 else dur
rate = (t_off - t_on) / sum(W)

N, S = len(lines), len(sil)
INF = float('inf')
# boundary candidates: index 0 = speech onset, 1..S = silences, S+1 = offset
pts = [(t_on, t_on)] + sil + [(t_off, t_off)]
def cost(i, a, b):  # line i spans from end of pause a to start of pause b
    d = pts[b][0] - pts[a][1]
    if d <= 0: return INF
    exp = W[i] * rate
    bonus = (pts[b][1] - pts[b][0]) if b != S + 1 else 0
    return ((d - exp) / max(exp, 1)) ** 2 - 0.6 * bonus
dp = [[INF] * (S + 2) for _ in range(N + 1)]
bk = [[-1] * (S + 2) for _ in range(N + 1)]
dp[0][0] = 0
for i in range(N):
    for a in range(S + 2):
        if dp[i][a] == INF: continue
        for b in range(a + 1, S + 2):
            if (i < N - 1 and b == S + 1) or (i == N - 1 and b != S + 1): continue
            c = dp[i][a] + cost(i, a, b)
            if c < dp[i + 1][b]: dp[i + 1][b], bk[i + 1][b] = c, a
seq, b = [], S + 1
for i in range(N, 0, -1):
    a = bk[i][b]; seq.append((pts[a][1], pts[b][0])); b = a
seq.reverse()

res = {'duration': round(dur / tempo, 3), 'tempo': tempo,
       'lines': [{'t0': round(a / tempo, 3), 't1': round(b / tempo, 3), 'text': lines[i]} for i, (a, b) in enumerate(seq)]}
json.dump(res, open(out, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
for i, (a, b) in enumerate(seq):
    print(f'{i:2d} {a:6.2f}-{b:6.2f} ({b - a:5.2f}s, exp {W[i] * rate:5.2f})  {lines[i][:50]}')
