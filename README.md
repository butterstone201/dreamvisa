# Dream Visa Guide: Google AdSense Kit

Files for getting https://dreamvisaguide.com (WordPress) approved for Google AdSense and running ads correctly.

| Path | What it is |
|---|---|
| `dreamvisa-adsense/` | A small WordPress plugin. It adds the AdSense verification tag and ad script, serves `/ads.txt`, provides a `[dv_ad]` shortcode for manual ad units, and keeps ads off legal and contact pages and away from admins. |
| `pages/*.html` | Ready-to-paste content for the pages AdSense reviewers look for: Privacy Policy (with the required Google/AdSense cookie disclosure), Disclaimer, About Us, Contact Us, and Terms & Conditions. |

---

## Step 1: Fix content first (the most common reason for rejection)

AdSense rejects most new sites for **"Low value content"**. Before you apply:

- [ ] **Publish at least 25–30 original, useful articles** of 1,000+ words each. Don't copy or lightly rewrite embassy pages or other blogs, and don't publish raw AI output. Add your own explanations, step-by-step checklists, fees tables, and real examples.
- [ ] **Link every guide to the official source** (government or embassy site), and show a "Last updated" date.
- [ ] **No misleading claims.** Remove phrases like "100% guaranteed visa", "visa approval guaranteed", or "get a job abroad without any documents", and remove fake job or visa offers. Google treats these as misrepresentation.
- [ ] **Don't look like a government site.** Don't use government logos, flags, or seals in your branding, and don't use titles like "Official Visa Portal".
- [ ] **No "apply now / pay here" flows** for visas or jobs, and don't collect passport data.
- [ ] Remove empty categories, "Hello world!", "Sample Page", lorem ipsum, and any posts with only an image or a couple of lines.
- [ ] Clear menus: Home, main categories (e.g. Student Visa, Work Visa, Tourist Visa, Immigration), About, Contact.
- [ ] Footer links: **Privacy Policy · Disclaimer · Terms & Conditions · About Us · Contact Us**.

## Step 2: Add the required pages

For each file in `pages/`:

1. WordPress admin → **Pages → Add New** (for Privacy Policy, edit the existing page if there is one).
2. Click **⋮ (top right) → Code editor** and paste the file content.
3. Replace **every** `[BRACKETED]` placeholder with real information, such as your name, email, date, and country.
4. Set the slug (URL) shown at the top of the file, e.g. `privacy-policy`, `disclaimer`, `about-us`, `contact-us`, `terms-and-conditions`.
5. **Settings → Privacy**: select the new Privacy Policy page.
6. **Appearance → Menus** (or **Editor → Footer** for block themes): add all five pages to the footer.

For the contact form, install **Contact Form 7** or **WPForms Lite**, create a form, and put its shortcode in `contact-us.html`.

## Step 3: Technical basics

- [ ] **HTTPS** works on every page with no mixed-content warnings.
- [ ] **Settings → Reading**: make sure "Discourage search engines from indexing this site" is **unchecked**.
- [ ] An SEO plugin (Yoast / Rank Math) with an XML sitemap, submitted in **Google Search Console**, and the pages indexed.
- [ ] A fast, mobile-friendly theme (e.g. GeneratePress, Astra, Kadence). Check with PageSpeed Insights.
- [ ] No broken links or 404s in the menus.
- [ ] Remove any other ad networks or popups before applying.

## Step 4: Install the plugin and apply

1. Use `dreamvisa-adsense.zip` from this repo (or zip the `dreamvisa-adsense` folder yourself), then go to **Plugins → Add New → Upload Plugin**, and activate it.
   (Or upload the folder to `wp-content/plugins/` via FTP or the hosting File Manager.)
2. Sign up at https://adsense.google.com and add the site `dreamvisaguide.com`.
3. Copy your **Publisher ID** (`pub-XXXXXXXXXXXXXXXX`) and paste it into **Settings → DreamVisa AdSense**, then save.
4. Open `https://dreamvisaguide.com/ads.txt` and check that it shows:
   `google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0`
   (If a physical `ads.txt` file already exists in your web root, edit that file instead. It takes priority.)
5. In AdSense, choose the **"AdSense code snippet"** or **meta tag** verification method. The plugin already outputs both, so click **Verify**, then **Request review**.
6. Review usually takes from a few days to 2–4 weeks. Don't change themes or delete content during review.

> Only use **one** method to add the AdSense code. If Site Kit, Ad Inserter, or your theme already adds it, turn that off or don't use this plugin.
> The plugin hides ads from logged-in admins, so check your ads in a private/incognito window.

## Step 5: Privacy consent (EEA / UK / Switzerland)

Google requires a Google-certified consent banner for visitors from Europe. The simplest free option is:
**AdSense → Privacy & messaging → European regulations → Create message**, then publish it for the site. Also add a "Privacy and cookie settings" link in the footer (the message setup explains how). You don't need a separate cookie plugin for ads.

## Step 6: After approval: ad placement

- Turn on **Auto ads** in AdSense (Ads → By site → edit). Start with in-page ads plus anchor ads, and leave vignettes off if they hurt the experience.
- Optionally, add manual units: create a display ad unit in AdSense, copy its `data-ad-slot` number, and add a **Shortcode** block to posts:
  - `[dv_ad slot="1234567890"]` for a responsive display ad
  - `[dv_ad slot="1234567890" format="fluid" layout="in-article"]` for an in-article ad
- **Policy rules to follow:**
  - Never click your own ads, and never ask others to click them.
  - Don't place ads so they look like download or "Apply" buttons or navigation.
  - Don't put ads on pages with little or no content. The plugin excludes the contact and legal pages by default.
  - Don't buy low-quality traffic (traffic exchanges, bots).
