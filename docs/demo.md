# Demo script: ten minutes, main journey (issue #40)

## Before you start

```bash
php artisan migrate:fresh --seed        # demo data (never in production)
npm run build && php artisan serve      # http://127.0.0.1:8000
php artisan queue:work                  # optional: sends the queued e-mails and translations
```

Every demo account has the password `password`. In development the login page lists the main accounts with a **Use** button that fills the form (hidden in production).

| Role | Email | Notes |
|------|-------|-------|
| Admin | `admin@ahla.test` | lands in `/admin`; `AHLA_ADMIN_PASSWORD` in `.env` overrides the password |
| Tourist | `tourist@ahla.test` (Lea, French) | has one confirmed booking with Amel |
| Tourist | `sam@ahla.test` (Sam, English) | has a pending request to Youssef |
| Guide (verified) | (logs in to `/host/dashboard`) |
| Host (verified) | `amel@ahla.test`, `youssef@ahla.test`, `ines@`, `mehdi@`, `sana@`, `karim@`, `rania@`, `walid@`, `leila@`, `hichem@ahla.test` | ten cities and languages |
| Host (pending) | `omar@ahla.test` | application waiting for the admin |
| Host (rejected) | `nadia@ahla.test` | shows the "apply again" path |

## The walkthrough

1. **Home (1 min).** Open the home page. Switch the language with the buttons in the header: English, Français, العربية. Arabic flips the whole layout right to left. Shrink the window to phone width: the menu collapses.
2. **Find a host (2 min).** Click *Find a host*. Choose city *Tunis*, language *Français*, interest *Food*, press *Search*. Each card shows the **Verified** badge, a match percentage and the **"Why this host"** tags (for example "Speaks Français", "Shares your interest: Food"). Point out Mehdi: new on Ahla, no rating, still listed. Try a city with nobody (Tozeur plus an interest nobody has) to show the empty state.
3. **Book (2 min).** Log in as `tourist@ahla.test`. Open Amel's profile and press *Request booking* on "A market morning". Pick a date, a time between 09:00 and 17:00 and 2 people. The page says the card is only reserved now. Send the request. The booking page shows *Requested* and *Reserved*, and no phone number yet.
4. **Host accepts (2 min).** Log out, log in as `amel@ahla.test`. *Booking requests* shows the request with the time limit. Press *Accept*. The booking becomes *Confirmed*, the payment *Paid*. Try to request a second booking at an overlapping time as another tourist: the page explains that the host is already booked.
5. **Chat (1 min).** Open the booking, *Open the chat*. As Lea write in French, as Amel answer in English: each side reads the other's message in their own language (when the translation service is running) and can press *Show original*. Without the service, the original is shown with "Translation unavailable".
6. **Admin (1 min).** Log in as `admin@ahla.test`. *Admin* lists host applications: open Omar's, download the (encrypted at rest) document, schedule a video call with any https link, approve. Open *Bookings and payments* and the *Audit log* to see who changed what.
7. **Cancellation (1 min).** As Amel (or as Lea) cancel a confirmed booking. A host cancelling gets the guest refunded in full. When the start is less than 24 hours away, the guest's booking page also lists up to three other verified hosts in the same city.

## Things to point out

- Nothing is confirmed until the payment is captured. Declined, expired and cancelled bookings release or refund the money.
- A host's phone number appears only after confirmation, and only if the host agreed (Profile > phone checkbox).
- Failed e-mail or translation never stops a booking.

## Guide space and admin space (extra five minutes)

- **Guide (`amel@ahla.test`)** lands in the guide space: requests to answer with a countdown, the next seven days, an earnings chart (the seed adds forty past bookings), the setup checklist and the latest messages. Open *Clients* for stages (awaiting, upcoming, returning, past), a client's history and a private note only the guide can read. *Messages* is the inbox of all booking chats; *Calendar* shows bookings and days off; *Earnings* is net of refunds and exports CSV. Log in as `youssef@ahla.test` to see a pending request with *Accept* and *Decline* on the overview.
- **Admin (`admin@ahla.test`)** lands on the platform overview: totals, bookings per day, net volume per month, bookings by status and a "Needs attention" list. *Users* searches and filters every account and can lock one (it is logged out at once). *Guides* compares guides by rating, bookings, cancellation rate and volume, with suspend and reinstate. *Revenue* shows paid, refunded, net volume and the indicative revenue at the configured rate (`AHLA_COMMISSION_RATE`, planning figure only, ADR 0007).
