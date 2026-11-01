# How to use Ahla

A step-by-step guide for the three kinds of people who use the app. Everything below was clicked through in a real browser, with two people (a tourist and a guide) using it at the same time.

## 0. Open the app and pick an account

1. Start it: `php artisan serve` (open **http://127.0.0.1:8000**, or the port you chose, for example `--port=8099`).
2. Open the **Log in** page. In development it shows a **Demo accounts** panel: one click on **Log in** next to an account signs you in. All demo accounts use the password `password`.
3. **Switching accounts** needs no logging out. The Log in and Sign up pages stay open while you are signed in; they say who is signed in, and logging in as someone else simply replaces them. One browser holds one person at a time, so to watch two people at once (for example a tourist and a guide chatting) use a normal window and a private/incognito window.
4. **Creating a new account** works any time: **Sign up**, choose *tourist* or *guide*, pick a password of at least 8 characters. Use any e-mail address (no confirmation e-mail is required).

| Demo account | Password | Lands in |
|---|---|---|
| `admin@ahla.test` | `password` | the admin dashboard |
| `amel@ahla.test` (guide) | `password` | the guide dashboard |
| `youssef@ahla.test` (guide with a pending request) | `password` | the guide dashboard |
| `tourist@ahla.test` | `password` | the home page |
| `omar@ahla.test` (guide waiting for verification) | `password` | the guide dashboard |

## 1. As a tourist

1. **Sign up** as *tourist* (or log in as `tourist@ahla.test`).
2. **Profile** (top right): add the languages you speak and your interests. Ahla uses them to rank hosts for you. Your phone number is private; you choose whether the guide may see it once a booking is confirmed.
3. **Find a host**: pick a city and language, add filters on the left (date, people, price, rating, interests), press **Search**. Each card shows *Verified*, a match percentage and **Why this host** (for example "Speaks Français", "Shares your interest: Food").
4. Open a host, press **Request booking** under the experience you like. Choose a date, a start time inside the guide's weekly hours (listed on the page) and the number of people, then **Send request**.
5. What happens next: your card is only **reserved**. The guide has **12 hours** to answer. If they accept, the payment is taken and the booking becomes *Confirmed*. If they decline or do not answer, the reservation is released and nothing is charged. (Payments use a sandbox: no real money moves.)
6. **My bookings** (top menu) shows every booking with its status and payment. Open one to see the details. The guide's e-mail and phone appear only after the booking is *Confirmed*.
7. **Chat**: on a booking, press **Open the chat**. Write and send. The guide's answer appears by itself within about five seconds, no reload needed. If a translation service is connected, messages are translated between English, French and Arabic and you can press **Show original**; without one, each foreign-language message shows "Translation unavailable - showing the original".
8. **Cancel**: on a confirmed booking press **Cancel this booking**. Refund rule: 100% if at least 48 hours before the start, 50% from 24 to 48 hours, nothing under 24 hours. If the *guide* cancels, you are always refunded in full, and if it is less than 24 hours before the start you also see other verified hosts in the same city.
9. **Notifications** (top menu): a bell-style list of everything that happened to your bookings. You can switch e-mail copies off there.

## 2. As a guide (host)

1. **Sign up** as *guide*. You land on your profile.
2. **Complete your host profile**: public name, a short bio, your city, languages and interests. Save.
3. **Verification** (Guide space > Verification): upload a photo or PDF of an identity document (max 5 MB; it is stored encrypted and only admins can open it). An admin then schedules a short video call and approves you. Until then your experiences are hidden from search; the dashboard banner tells you where you are.
4. **Experiences** (Guide space > Experiences > New experience): title, category, description, duration (1 to 6 hours), price per person in TND, group size (1 to 6), meeting point, interests, an optional photo. Tick **Published** to show it (published experiences of verified guides appear in search).
5. **Availability**: add your weekly hours (one row per day, no overlaps) and any **days off**. Tourists can only book inside these hours.
6. **Overview** (Guide space): requests waiting for your answer with a live countdown and **Accept / Decline** buttons, the next 7 days, a 6-month earnings chart and a setup checklist.
   - *Accept* captures the payment and confirms the booking (the app refuses if you already have 2 confirmed bookings that day or an overlapping one).
   - *Decline* releases the tourist's card.
7. **Calendar**: month view of bookings (blue = requested, green = confirmed, grey = done) and your days off.
8. **Clients**: everyone you have worked with, filter by stage (awaiting, upcoming, returning, past). Open a client for their history and a **private note** (only you can read it; ideal for allergies or interests).
9. **Messages**: one inbox for all booking chats with unread counts. Open a thread to answer.
10. **Earnings**: money actually paid minus refunds, by month, with **Export CSV**. Ahla takes no commission in this version.
11. **Cancelling** a confirmed booking refunds the tourist in full automatically.

## 3. As an admin

1. Log in as `admin@ahla.test`. You land on the **platform overview**: users, verified guides, bookings, net volume, bookings per day, volume per month, bookings by status and a **Needs attention** list (applications waiting, requests about to expire, refunds still pending, locked accounts).
2. **Applications** (admin menu > Applications): open one, **Download identity document**, **Schedule the video call** (date, time and an https link such as a Jitsi room), then **Approve** (the call must be scheduled first) or **Reject** (a reason is required and shown to the guide). The guide appears in search right after approval.
3. **Users**: search by name or e-mail, filter by role or status, open anyone to see their bookings and history. **Disable account** (with a reason) logs the person out at once and blocks login; **Re-enable** undoes it. Admin accounts cannot be disabled from here.
4. **Guides**: compare guides by rating, live experiences, bookings, cancellation rate and volume. **Suspend** (reason required) hides a guide from search; **Reinstate** brings them back.
5. **Bookings**: every booking with its status and payment; filter by status.
6. **Revenue**: money paid by clients, refunded, **net volume**, month by month for a year, top guides and top cities, with **Export CSV**. *Indicative revenue* is net volume times `AHLA_COMMISSION_RATE` (default 10%): a planning figure only, nothing is charged (ADR 0007).
7. **Audit log**: the timeline of everything that happens: sign-ups, logins and logouts, failed logins, profile changes, experience and availability changes, messages sent (who wrote, never what), booking and payment status changes, admin decisions. Filter by type with the chips at the top.

## 4. Try the whole story in ten minutes

1. Window A: sign up as a **guide** (*Sami*, city Sousse), complete the profile, upload any image as ID, create and publish an experience, add weekly hours.
2. Window B: log in as **admin**, approve Sami's application (schedule a call first).
3. Window C (private): sign up as a **tourist**, search Sousse, request Sami's experience.
4. Window A: Overview > **Accept**. Window C: the booking is *Confirmed*.
5. Both: open the booking chat and talk; messages show up on the other side within seconds.
6. Window B: **Audit log** shows every step; **Revenue** shows the payment.

## 5. If something looks wrong

| You see | Do this |
|---|---|
| "Your page expired" | The form was open too long (a session ends after 30 minutes without activity) or is from an old tab. Just submit again; your text is kept, your password is not. |
| Old design or old data | `git pull`, `composer install`, `npm ci`, `npm run build`, `php artisan migrate:fresh --seed`, restart the server. Check only one server is running on your port. |
| "Port already in use" | Another `php artisan serve` is running. Close its terminal or use `--port=8100`. |
| Log in page redirects to the home page | You are already signed in; open `/login` again to see the switch panel, or press Log out. |
| Messages not translated | No translation service is configured (the default). The chat shows the original with a calm "Translation unavailable" note. See ADR 0006 to connect one. |
| Emails not arriving | In development mail goes to `storage/logs/laravel.log` (`MAIL_MAILER=log`). |
| A guide does not appear in search | They must be **verified**, have a **published** experience, and (to be bookable) weekly hours. |
