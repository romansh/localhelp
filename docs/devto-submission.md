---
title: "LocalHelp: A Map Where Neighbors Help Each Other in One Click [DEV Weekend Challenge: Community]"
published: true
tags: devchallenge, weekendchallenge, showdev
---

*This is a submission for the [DEV Weekend Challenge: Community](https://dev.to/challenges/weekend-2026-02-28)*

## The Community

I'm from Ukraine. Over here, volunteering and mutual aid are not buzzwords. They are survival skills.

Since 2022, our communities have learned to self-organize at a speed that surprises even ourselves. When pharmacies run out of ibuprofen, a neighbor finds it. When an elderly person needs a ride to the hospital, someone nearby drives them. When the power is out and you need a phone charger, you post in a group chat and three people respond in minutes.

This happens every day. But it happens through messy group chats, lost messages, and zero structure. Requests get buried. People who want to help never see the ones who need it.

LocalHelp is my attempt to fix that: **a map where you post what you need, and the closest person who can help sees it first.**

## What I Built

**LocalHelp** is a real-time, map-based micro-volunteering platform. The idea is dead simple:

1. You see a map of your neighborhood
2. You pin a request for help (groceries, medicine, transport, anything)
3. A neighbor sees it, clicks "I'll help", and both of you instantly get each other's contact
4. When the help arrives, you close the request

No middleman. No coordination overhead. No app store. Just a browser and a map.

The whole interaction takes about 15 seconds from "I need help" to "someone is on the way."

### The map tells you everything at a glance

![Map overview with colored markers and popup](https://dev-to-uploads.s3.amazonaws.com/uploads/articles/ds8wj0jk49m0yg5nsiyi.png)

Every category has its own color: blue for groceries, red for medicine, purple for transport, teal for everything else. You see the whole picture without clicking a single marker. Filter by category, draw a custom area on the map, and the list updates in real time.

Tap any marker to see the full details, contact info, and a single "I'll help" button.

### Once a neighbor takes your request, you know immediately

![Owner view with helper info and visual markers](https://dev-to-uploads.s3.amazonaws.com/uploads/articles/l35umc561le39f47axn9.png)

No page reload. No email notification to check later. The marker turns orange the moment someone takes it, and the helper's name and contact appear right in the popup. WebSockets make this instant for everyone on the map.

Your own requests have a white center dot so you can always spot them. The "Mark done" button closes the loop when help has arrived.

### Click any marker for full details

Tap a marker to open a popup with everything you need: description, category, contact info, and deadline. If it is open, you will see a big "I'll help" button. If someone already took it, you will see who is helping.

![Marker popup — open request with I'll help button](https://dev-to-uploads.s3.amazonaws.com/uploads/articles/8huedspmxdwtvzrd4nyb.png)

![Marker popup — taken request with helper info](https://dev-to-uploads.s3.amazonaws.com/uploads/articles/th80sbs5yrogwiw0o4ir.png)

### Track everything you asked for in one place

![My Needs modal showing in-progress and fulfilled requests](https://dev-to-uploads.s3.amazonaws.com/uploads/articles/lggqygn8ccus44mfsjy9.png)

The "My Needs" panel shows all your active requests: who took them, their contact info, deadline, and full history. Fulfilled requests stay visible until they expire, so nothing disappears when you are not looking.

### Manage what you are helping with

![My Help modal showing assigned tasks with deadlines](https://dev-to-uploads.s3.amazonaws.com/uploads/articles/bk0244w77nhe7bc3pjln.png)

The "My Help" panel is your volunteer dashboard. Every task you committed to, with the requester's contact, category, and hard deadline. Changed your mind? Hit "Give up" and the request goes back to the map as open. No guilt, no friction.

## Demo

No live instance at the moment, but the screenshots above show the full flow. Here is how to run it yourself in under 2 minutes:

```bash
git clone https://github.com/romansh/localhelp && cd localhelp
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
npm run build
php artisan serve          # App
php artisan reverb:start   # WebSocket server
php artisan queue:listen   # Broadcast worker
```

Docker with Traefik + Cloudflare Tunnel is also supported:

```bash
docker compose up -d
```

## Code

{% github https://github.com/romansh/localhelp %}

Key parts of the codebase:

- **Livewire v4 components** handle all interactivity without writing a single REST endpoint
- **Laravel Reverb** (WebSockets) pushes marker updates to every connected client in real time
- **Leaflet.js + Leaflet Draw** power the map, custom markers, area selection, and popups
- **Google OAuth** for one-click login (no passwords to manage)
- **Anti-spam**: reCAPTCHA, daily rate limits, keyword blacklist
- **Auto-expiration**: requests disappear after 1h to 7 days (user's choice)
- **SQLite**: zero infrastructure, works out of the box

## How I Built It

**Stack:** Laravel 12, Livewire v4, Alpine.js, TailwindCSS v4, Leaflet.js, Laravel Reverb, SQLite

**Timeline:**

- **Friday + Saturday** - thinking, sketching, choosing between ideas. My first concept was actually a tournament scheduling app for a local table tennis league I play in. It would account for power outages and air raid alerts when rescheduling matches. That is a real problem where I live. It would have been a fun build, but the audience is tiny and the potential stops at one league. Then I thought: what if I build something that any neighborhood can use, and that can later be narrowed down to any niche? That is how LocalHelp was born.
- **Sunday** - all the coding. Database schema, Google OAuth, Leaflet map, markers, popups, CRUD, real-time broadcasting, category filters, area selection, helper workflow, My Needs / My Help panels, visual marker system, Ukrainian translation, spam protection.
- **Sunday night into Monday** - Docker setup, polish, screenshots, this post.

**Design decisions that mattered:**

1. **Map-first, not list-first.** When you need help from a neighbor, distance is the most important filter. A list cannot show you that. A map does it instantly.

2. **No chat, no comments, no threads.** The app gives you a phone number or Telegram handle. You call. You text. The real conversation happens where people already communicate. The app just connects you.

3. **Visual status on markers.** You should never have to click a marker to know if a request is taken or fulfilled. Orange ring = someone is helping. Faded = done. White dot = yours. This was inspired by how traffic lights work: color carries meaning before you read anything.

4. **SQLite by default.** This is a neighborhood tool. It does not need Postgres until it serves 10,000 people. Zero-config setup means anyone can fork it and run it for their block in under 2 minutes.

**Why this matters beyond a hackathon:**

LocalHelp is intentionally generic. It could be the starting point for something more specific: a tool for a running club to coordinate rides to races, a neighborhood watch reporting system, a mutual aid network for a refugee community, or a disaster response coordination board. The map-first, real-time, one-click-to-connect pattern works for any community where proximity matters. Fork it, change the categories, and you have a boilerplate for your own niche.

In Ukraine, we learned that the best systems are the ones people actually use under stress. They need to be fast, obvious, and forgiving. That is what I tried to build this weekend.

**A note on AI:** I used GitHub Copilot Chat in VS Code. Maybe a little. Maybe not so little. But here is the thing: Copilot does not know what your community needs. It does not know that orange means "taken" and gray means "done" in your head. It does not know that the popup should show a phone number, not an email. AI is a power tool, but you still need to know what you are building and why. The intent, the UX decisions, the architecture, the flow from "I need help" to "someone is coming" - that is all human.

---

*Built with care from Ukraine.* 
