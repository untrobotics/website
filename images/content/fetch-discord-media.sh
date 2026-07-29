#!/bin/bash
# Downloads curated UNT Robotics photos/videos from the Discord CDN (URLs still
# valid from this session's scrape). Run soon — Discord CDN links expire ~24h.
# Curated from the media catalog. Idempotent.
set -u
cd "$(dirname "$0")"

get() { # url outpath
  local url="$1" out="$2"
  mkdir -p "$(dirname "$out")"
  curl -sL "$url" -o "$out" -w "  %{http_code} %{size_download}b  $out\n"
  if file "$out" 2>/dev/null | grep -qi 'HTML\|text'; then echo "    !! $out not media (expired?) — removing"; rm -f "$out"; fi
}

echo "== Rocketry / Aerospace (High-Power Rocketry) =="
get "https://cdn.discordapp.com/attachments/755879832486805696/1266563480182460569/IMG_1873.jpg" aerospace/hpr-launch-prep.jpg
get "https://cdn.discordapp.com/attachments/755879832486805696/1262098276015013888/IMG_1799.jpg" aerospace/hpr-custom-paint.jpg
get "https://cdn.discordapp.com/attachments/755879832486805696/1233788661611364433/IMG_1574.jpg" aerospace/hpr-parachute-sewing.jpg
get "https://cdn.discordapp.com/attachments/755879832486805696/1213196706192298084/IMG_1298.jpg" aerospace/hpr-fiberglassing.jpg
get "https://cdn.discordapp.com/attachments/755879832486805696/1121278437823369296/IMG_0664.jpg" aerospace/hpr-l2-clearcoat.jpg
get "https://cdn.discordapp.com/attachments/676948293426741259/899364686926073896/image2.jpg" aerospace/hpr-third-scale-model.jpg

echo "== 3D Printing / Design =="
get "https://cdn.discordapp.com/attachments/676948293426741259/1011386187333775390/IMG_9420.jpg" printing/nosecone-ocean-pattern.jpg
get "https://cdn.discordapp.com/attachments/676948293426741259/900374353823277086/image0.jpg" printing/finished-print.jpg
get "https://cdn.discordapp.com/attachments/676948293426741259/1011664213808259092/IMG_9425.jpg" printing/paint-booth.jpg

echo "== JPL / Eagles Nest Rover =="
get "https://cdn.discordapp.com/attachments/894034907536453715/940675944639762442/IMG_0185.jpg" rover/six-motors.jpg
get "https://cdn.discordapp.com/attachments/894034907536453715/1087555793731195011/IMG_5822.jpg" rover/system-integration.jpg
get "https://cdn.discordapp.com/attachments/894034907536453715/1044429038892089424/IMG_5195.jpg" rover/workshop-wide.jpg
get "https://cdn.discordapp.com/attachments/894034907536453715/1081664257273372712/IMG_0190.jpg" rover/raspberry-pi-wiring.jpg

echo "== Scrapp-E =="
get "https://cdn.discordapp.com/attachments/1286515459402895482/1356351728651337959/IMG_20250331_135113878_HDR.jpg" scrappe/build-hdr.jpg
get "https://cdn.discordapp.com/attachments/1286515459402895482/1357869602968699122/IMG_0041.jpg" scrappe/first-chassis-mount.jpg
get "https://cdn.discordapp.com/attachments/1286515459402895482/1447356587923472516/IMG_4540.jpg" scrappe/build-progress.jpg

echo "== Sofabot =="
get "https://cdn.discordapp.com/attachments/765377092132405248/1297822665591226441/IMG_6800.jpg" sofabot/build-1.jpg
get "https://cdn.discordapp.com/attachments/765377092132405248/1301627761890300004/IMG_6847.jpg" sofabot/circle-done.jpg
get "https://cdn.discordapp.com/attachments/765377092132405248/1074114022355509248/A3D184FD-8A2E-4072-8A96-26C4162573F0.jpg" sofabot/early-build.jpg

echo "== Outreach / Team =="
get "https://cdn.discordapp.com/attachments/1020829492417155192/1041547665453895720/IMG_9715.jpg" outreach/scouts-stem.jpg
get "https://cdn.discordapp.com/attachments/1020829517960462417/1101631837140435065/IMG_0470.jpg" events/group-work-session.jpg
get "https://cdn.discordapp.com/attachments/674703370971250708/1078181184397377537/BF1CF394-204F-4834-B980-E4EE1E520411.jpg" events/meeting-pics.jpg

echo "== Videos =="
get "https://cdn.discordapp.com/attachments/765377092132405248/911457568306638888/Sofabot1.mov" video/sofabot-first-drive.mov
get "https://cdn.discordapp.com/attachments/1286515459402895482/1308496373057851412/Spring_Assembly.mp4" video/scrappe-spring-assembly.mp4

echo "done."
