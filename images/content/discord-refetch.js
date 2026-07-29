'use strict';
// Re-fetch curated Discord images with FRESH signed URLs via the bot (REST),
// then download to disk. Uses channelId+msgId (stable) from the media catalog;
// the bot re-signs the CDN URL so expiry doesn't matter. Env: DISCORD_TOKEN.
const fs = require('fs');
const path = require('path');
const https = require('https');

const TOKEN = process.env.DISCORD_TOKEN;
if (!TOKEN) { console.error('DISCORD_TOKEN not set'); process.exit(1); }
const API = 'https://discord.com/api/v10';

// [channelId, msgId, filenameHint (or ''), outPath]
const ITEMS = [
  // Rocketry / Aerospace
  ['755879832486805696','1266563482921341021','IMG_1873','aerospace/hpr-launch-prep.jpg'],
  ['755879832486805696','1262098277336223797','IMG_1799','aerospace/hpr-custom-paint.jpg'],
  ['755879832486805696','1233788662102360175','IMG_1574','aerospace/hpr-parachute-sewing.jpg'],
  ['755879832486805696','1213196706741620746','IMG_1298','aerospace/hpr-fiberglassing.jpg'],
  ['755879832486805696','1121278439895347230','IMG_0664','aerospace/hpr-l2-clearcoat.jpg'],
  ['676948293426741259','899364703908806726','image2','aerospace/hpr-third-scale-model.jpg'],
  // 3D printing
  ['676948293426741259','1011386188617236531','IMG_9420','printing/nosecone-ocean-pattern.jpg'],
  ['676948293426741259','900374357510070302','image0','printing/finished-print.jpg'],
  ['676948293426741259','1011664215288856697','IMG_9425','printing/paint-booth.jpg'],
  // Rover
  ['894034907536453715','940675945751265332','IMG_0185','rover/six-motors.jpg'],
  ['894034907536453715','1087555795383750747','IMG_5822','rover/system-integration.jpg'],
  ['894034907536453715','1044429039210876948','IMG_5195','rover/workshop-wide.jpg'],
  ['894034907536453715','1081664257688612924','IMG_0190','rover/raspberry-pi-wiring.jpg'],
  // Scrapp-E
  ['1286515459402895482','1356351731071586414','','scrappe/build-hdr.jpg'],
  ['1286515459402895482','1357869602855588021','IMG_0041','scrappe/first-chassis-mount.jpg'],
  ['1286515459402895482','1447356590167560192','IMG_4540','scrappe/build-progress.jpg'],
  // Sofabot
  ['765377092132405248','1297822669823152140','IMG_6800','sofabot/build-1.jpg'],
  ['765377092132405248','1301627762179571733','','sofabot/circle-done.jpg'],
  ['765377092132405248','1074114022833664041','','sofabot/early-build.jpg'],
  // Outreach / team
  ['1020829492417155192','1041547665986555924','IMG_9715','outreach/scouts-stem.jpg'],
  ['1020829517960462417','1101631838130290862','IMG_0470','events/group-work-session.jpg'],
  ['674703370971250708','1078181184934268948','','events/meeting-pics.jpg'],
  // Videos
  ['765377092132405248','911457571406249984','Sofabot1','video/sofabot-first-drive.mov'],
  ['1286515459402895482','1308496373670477894','Spring_Assembly','video/scrappe-spring-assembly.mp4'],
];

function getJSON(url) {
  return new Promise((res, rej) => {
    https.get(url, { headers: { Authorization: 'Bot ' + TOKEN, 'User-Agent': 'untrobotics-media/1.0' } }, (r) => {
      let d = ''; r.on('data', (c) => d += c); r.on('end', () => {
        if (r.statusCode === 429) { const j = JSON.parse(d); return setTimeout(() => res(getJSON(url)), (j.retry_after || 2) * 1000 + 200); }
        try { res({ code: r.statusCode, data: JSON.parse(d) }); } catch (e) { res({ code: r.statusCode, data: null }); }
      });
    }).on('error', rej);
  });
}
function download(url, out) {
  return new Promise((res) => {
    fs.mkdirSync(path.dirname(out), { recursive: true });
    const f = fs.createWriteStream(out);
    https.get(url, (r) => {
      if (r.statusCode !== 200) { f.close(); fs.unlinkSync(out); return res({ ok: false, code: r.statusCode }); }
      r.pipe(f); f.on('finish', () => f.close(() => res({ ok: true, bytes: r.headers['content-length'] })));
    }).on('error', () => res({ ok: false }));
  });
}

(async () => {
  let ok = 0, fail = 0;
  for (const [ch, msg, hint, out] of ITEMS) {
    const r = await getJSON(`${API}/channels/${ch}/messages/${msg}`);
    if (!r.data || !r.data.attachments || !r.data.attachments.length) { console.log('  NO MSG/ATTACH', out, r.code); fail++; continue; }
    let att = r.data.attachments.find((a) => hint && a.filename.includes(hint)) || r.data.attachments.find((a) => (a.content_type || '').startsWith('image/') || (a.content_type || '').startsWith('video/')) || r.data.attachments[0];
    const dl = await download(att.url, path.join(__dirname, out));
    if (dl.ok) { console.log('  OK  ', out, att.filename); ok++; } else { console.log('  FAIL', out, dl.code); fail++; }
    await new Promise((s) => setTimeout(s, 200));
  }
  console.log(`\ndone: ${ok} ok, ${fail} failed`);
})();
