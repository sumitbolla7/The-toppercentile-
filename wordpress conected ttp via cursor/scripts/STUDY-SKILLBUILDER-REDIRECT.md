# Redirect study.thetoppercentile.co.in/skillbuilder → thetoppercentile.co.in/exam/

The study subdomain is hosted on **TCY** (white-label). WordPress on the main domain cannot intercept those requests unless DNS points study to the same server.

## 1. WordPress (done in repo)

Upload `wp-content/mu-plugins/ttp-google-sitelinks-seo.php` (v1.1.0). It redirects `/skillbuilder` if that URL ever hits WordPress (main or study alias domain).

## 2. Hostinger redirect (recommended)

1. Log in to **Hostinger hPanel**
2. **Domains** → **Redirects** (or **study.thetoppercentile.co.in** → **Redirects**)
3. Add redirect:
   - **From:** `https://study.thetoppercentile.co.in/skillbuilder`
   - **To:** `https://thetoppercentile.co.in/exam/`
   - **Type:** Permanent (301)

## 3. TCY partner panel

If `study.thetoppercentile.co.in` DNS points to TCY only, ask **TCY support** to add a redirect:

- `/skillbuilder` → `https://thetoppercentile.co.in/exam/`

(Currently `/skillbuilder` returns TCY 404 — see https://study.thetoppercentile.co.in/skillbuilder)

## 4. Apache on subdomain folder (if study has its own public_html)

Copy rules from `scripts/study-skillbuilder-redirect.htaccess` into the study subdomain `.htaccess`.

## Verify

- Open https://study.thetoppercentile.co.in/skillbuilder — should land on https://thetoppercentile.co.in/exam/
- In Google Search Console, request re-index of the old URL after redirect is live.
