#!/usr/bin/env python3
"""SFX track for the ads (synthesised, no samples). Music bed only with --music
(the client asked for no music: voice only + subtle SFX).

usage: soundtrack.py <events.json> <duration_s> <out.wav> [--music]
events.json: [[t_seconds, "whoosh"|"pop"|"stamp"|"ding"|"rise"|"tick"], ...]
Writes a stereo 48 kHz wav: soft music bed (~-30 LUFS-ish) + SFX. The VO is
mixed on top by render.mjs.
"""
import json, sys, wave
import numpy as np

SR = 48000
rng = np.random.default_rng(7)

def env(n, a=0.005, r=0.2):
    t = np.arange(n) / SR
    e = np.minimum(1, t / max(a, 1e-4)) * np.exp(-t / r)
    return e

def lowpass(x, cutoff):
    # one-pole low-pass, vectorised enough for short sounds
    a = np.exp(-2 * np.pi * cutoff / SR); y = np.empty_like(x); acc = 0.0
    for i, v in enumerate(x):
        acc = (1 - a) * v + a * acc; y[i] = acc
    return y

def sfx(name):
    if name == 'pop':       # bubble pop: fast upward chirp
        n = int(.09 * SR); t = np.arange(n) / SR
        f = 500 + 1400 * t / t[-1]
        return .35 * np.sin(2 * np.pi * np.cumsum(f) / SR) * env(n, .002, .03)
    if name == 'tick':
        n = int(.03 * SR); return .25 * rng.standard_normal(n) * env(n, .0005, .004)
    if name == 'ding':      # two bells
        n = int(1.2 * SR); t = np.arange(n) / SR
        b = lambda f: np.sin(2 * np.pi * f * t) + .3 * np.sin(2 * np.pi * 2.76 * f * t)
        x = .28 * b(1318.5) * env(n, .002, .35)
        y = np.zeros(n); d = int(.11 * SR); y[d:] = (.28 * b(1975.5) * env(n, .002, .45))[:n - d]
        return x + y
    if name == 'stamp':     # thud + paper slap
        n = int(.5 * SR); t = np.arange(n) / SR
        thud = .9 * np.sin(2 * np.pi * (55 + 90 * np.exp(-t * 30)) * t) * env(n, .001, .12)
        slap = .5 * lowpass(rng.standard_normal(n), 2500) * env(n, .0005, .03)
        return thud + slap
    if name == 'whoosh':    # filtered noise swell
        n = int(.55 * SR); t = np.arange(n) / SR
        shape = np.sin(np.pi * t / t[-1]) ** 2
        noise = rng.standard_normal(n)
        x = lowpass(noise, 1800) - lowpass(noise, 300)
        return .5 * x * shape
    if name == 'rise':      # uplifting riser for the reveal
        n = int(1.4 * SR); t = np.arange(n) / SR
        f = 220 * (2 ** (2 * t / t[-1]))
        tone = .12 * (np.sin(2 * np.pi * np.cumsum(f) / SR) + .5 * np.sin(4 * np.pi * np.cumsum(f) / SR))
        noise = .18 * (lowpass(rng.standard_normal(n), 4000)) * (t / t[-1]) ** 2
        return (tone + noise) * np.minimum(1, (t[-1] - t) / .05)
    raise ValueError(name)

def music(dur):
    """100 bpm, Am - F - C - G, soft plucked arp + pad + sub + light kick/hat."""
    bpm = 100; beat = 60 / bpm; n = int(dur * SR)
    out = np.zeros((n, 2))
    chords = [[57, 60, 64], [53, 57, 60], [48, 52, 55], [55, 59, 62]]  # midi
    hz = lambda m: 440 * 2 ** ((m - 69) / 12)
    bar = 4 * beat
    t_all = np.arange(n) / SR
    for ci in range(int(dur / bar) + 1):
        ch = chords[ci % 4]; t0 = ci * bar
        i0 = int(t0 * SR); i1 = min(n, int((t0 + bar) * SR))
        if i0 >= n: break
        tt = t_all[i0:i1] - t0
        pad = sum(np.sin(2 * np.pi * hz(m) * tt) + .2 * np.sin(2 * np.pi * hz(m) * 2.003 * tt) for m in ch) / 3
        pad *= .05 * np.minimum(1, tt / .4) * np.minimum(1, (bar - tt) / .3)
        sub = .07 * np.sin(2 * np.pi * hz(ch[0] - 12) * tt) * np.minimum(1, tt / .05) * np.minimum(1, (bar - tt) / .1)
        out[i0:i1, 0] += pad + sub; out[i0:i1, 1] += pad + sub
        # 8th-note arp
        for k in range(8):
            m = ch[[0, 1, 2, 1][k % 4]] + 12 + (12 if k in (3, 7) else 0)
            s = int((t0 + k * beat / 2) * SR); L = int(.35 * SR)
            if s >= n: break
            L = min(L, n - s); tt2 = np.arange(L) / SR
            note = .045 * (np.sin(2 * np.pi * hz(m) * tt2) + .3 * np.sin(4 * np.pi * hz(m) * tt2)) * env(L, .003, .12)
            pan = .35 if k % 2 else .65
            out[s:s + L, 0] += note * (1 - pan) * 2; out[s:s + L, 1] += note * pan * 2
        # drums
        for k in range(4):
            s = int((t0 + k * beat) * SR)
            if s >= n: break
            L = min(int(.25 * SR), n - s); tt2 = np.arange(L) / SR
            kick = .16 * np.sin(2 * np.pi * (45 + 70 * np.exp(-tt2 * 35)) * tt2) * env(L, .001, .09)
            out[s:s + L] += kick[:, None]
            h = s + int(beat / 2 * SR)
            if h < n:
                L2 = min(int(.05 * SR), n - h)
                hat = .03 * rng.standard_normal(L2) * env(L2, .0005, .012)
                out[h:h + L2] += hat[:, None]
    fade = np.minimum(1, t_all / 1.5) * np.minimum(1, (dur - t_all) / 1.2)
    return out * fade[:, None]

def main():
    events, dur, out = json.load(open(sys.argv[1])), float(sys.argv[2]), sys.argv[3]
    mix = music(dur) if '--music' in sys.argv else np.zeros((int(dur * SR), 2))
    cache = {}
    for t, name in events:
        if name not in cache: cache[name] = sfx(name)
        x = cache[name]; s = int(t * SR)
        if s >= len(mix) or s < 0: continue
        L = min(len(x), len(mix) - s)
        mix[s:s + L] += x[:L, None] * .55
    peak = np.max(np.abs(mix)) or 1
    mix = mix / peak * .5
    pcm = (np.clip(mix, -1, 1) * 32767).astype('<i2')
    with wave.open(out, 'wb') as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR); w.writeframes(pcm.tobytes())
    print('wrote', out, len(events), 'sfx')

if __name__ == '__main__':
    main()
