#!/bin/bash
# Downloads curated UNT Robotics photos from Google Drive into images/content/.
# Photos: thumbnail endpoint (resized, web-ready JPEG). Logos: uc export (originals).
# Idempotent — re-running re-fetches. IDs curated from the Drive media catalog.
set -u
cd "$(dirname "$0")"

photo() { # id  outpath  [width]
  local id="$1" out="$2" w="${3:-1920}"
  mkdir -p "$(dirname "$out")"
  curl -sL "https://drive.google.com/thumbnail?id=${id}&sz=w${w}" -o "$out" \
    -w "  %{http_code} %{size_download}b  $out\n"
  # guard: if we got HTML instead of an image, flag it
  if file "$out" 2>/dev/null | grep -qi 'HTML\|text'; then echo "    !! $out is not an image (HEIC/auth) — removing"; rm -f "$out"; fi
}
orig() { # id  outpath   (full original, for SVG/PNG logos)
  local id="$1" out="$2"
  mkdir -p "$(dirname "$out")"
  curl -sL "https://drive.google.com/uc?export=download&id=${id}" -o "$out" \
    -w "  %{http_code} %{size_download}b  $out\n"
}

echo "== Aerospace / NASA Student Launch =="
photo 1Fto_rJU9YdxL-shlMAmTsqaKeNU7BIqM aerospace/nasa-sl-2022-launch-1.jpg
photo 1rRk_vfSVlmQfCYQpQCtmQosM7k2Byd9O aerospace/nasa-sl-2022-launch-2.jpg
photo 108-P2SNf6FEHfwg_Eao3lm3vX4bUy6xP aerospace/nasa-sl-2022-rocket.jpg
photo 1_7wgE2FZC8fbYAkWV0TedCPYqF_AtxYg aerospace/nasa-sl-2023-launch-1.jpg
photo 1ZBb-mxlo8cx42TYIk1Dy4ukO5KFcBtrw aerospace/nasa-sl-2023-launch-2.jpg
photo 1qcYdxTc7RPFVJv48kowynemATT59VDqR aerospace/nasa-sl-2023-launch-3.jpg
photo 1IFtCTRccwcMaw6t9emDnQ3fSRdvoHe1U aerospace/rocket-launch-still.jpg
photo 1yAXlhKE-WUIrUbUXuFEPGX-st2fyiEla aerospace/rocket-6shooter.jpg
photo 15CZy6aK14KI-v2JayTGP461XT2KqxEHl aerospace/rocket-amx-mav.jpg

echo "== JPL Rover =="
photo 1siahMtL8roCrqrK8L5n_kkBFRUENwKBv rover/jpl-rover.jpg 2000

echo "== Botathon =="
photo 14OfDNNkiSkIgDxMleRG9MwVoYdKcLjMm botathon/s7-1.jpg
photo 1YaqKdKiz5wKd6rFB9AoXsFgkz8hyVK0E botathon/s7-2.jpg
photo 1OYlinVFKhtnk1h7gdje09PYZcxLUlfKi botathon/s7-3.jpg
photo 1LO2vph6mMnbeZnVWevYRwfwJ0ItYrJRs botathon/s3-1.jpg
photo 1LRBg13CZocWPJCwt6zmVLzgFFWBRY9LZ botathon/s3-2.jpg
photo 1cMNN9eiii-ggbCHdBvxKGsDEY1emhjcW botathon/s1-2019.jpg

echo "== IEEE Region 5 2019 =="
photo 1dLgpc5Wt_OUqpEEzdQgMfUGO4UxNTMvl ieee2019/build-1.jpg
photo 1aPSG5Cewq-p0kaTBe0SCfXs-2I-HKkqw ieee2019/build-2.jpg
photo 1fQlqW00OpdZDVFMLyavSvex3DjmKmODV ieee2019/build-3.jpg
photo 1bmgW8WVOcRcIXYOlCB-1t5MAqOcoP2c0 ieee2019/r5-working.jpg
photo 1M6fBfLFE7pw_jRHr69QeWyalU0VKaLM2 ieee2019/r5-wiring.jpg

echo "== HackUNT 2024 (premium event photos) =="
photo 18wf25IFTHN2It6S1zmpFLdHcidecz_s- events/hackunt-2024-1.jpg
photo 18HmXBbG9PSym62lds9k2FJv6yXcYR8wv events/hackunt-2024-2.jpg
photo 1S0A1ZYqLNmMQRgnLgMKl-Vyg8UQvUXzN events/hackunt-2024-3.jpg
photo 1ftFP-s4Imr3_ZfqKThMY8FtY8jUIjTmG events/hackunt-2024-4.jpg
photo 1lH3-PClxMXkUTNjADaUelFBhIccSVJAq events/hackunt-2024-5.jpg

echo "== Meetings / Workshops =="
photo 1oC4RX1AACnqJlWgvIaFRv2kXxdaoP9oq events/wind-turbine-workshop.jpg
photo 1H4cKm-2-SX6H56Pvz6nF4H2bdJeYFme5 events/film-canister-rockets.jpg
photo 16HznI2OHmRsD4pDqJZzV8MUxnsFm2M2p events/general-meeting-1.jpg
photo 1kV3KbHruibmH4c5r0JN2TndCPdiJc2zD events/general-meeting-2.jpg

echo "== Rec Bots (Sofabot etc) =="
photo 1bffNlOytXa1c2aq7jM3lgRRHmoIx_oXR rec/recbot-1.jpg
photo 1YWWSUXixzS-7pekvCRcB5bYI8jB9tiRi rec/recbot-2.jpg
photo 1r-4sBWLbmdkTof070wXPUJIK_fJs2YvG rec/recbot-2020.jpg

echo "== Brand logos (originals) =="
orig 1MAP_TUE2ZoaLXH-WPb5bhuBOLPy_rynq logos/unt-robotics-horizontal.svg
orig 19lmZGLwJeNF-75zuFW6WbiP9lRi7O5Wm logos/unt-robotics.svg
orig 1TXoHyikUA5XpkiJfE39bYrarwhpWILUw logos/rocketbird.png

echo "done."
