# Requirement traceability (SRS FV -> tests)

Every **Must** and **Should** functional requirement of `docs/requirements/Ahla_SRS_FV.docx` and the non-functional requirements we can test automatically. Each test is written as `Class::method`. `tests/Feature/TraceabilityTest.php` fails if a requirement is missing from this file or a named test does not exist, so this table cannot silently go stale.

The acceptance journey `AcceptanceTest::test_at_main_journey_from_registration_to_late_host_cancellation` runs with the real host eligibility, availability checker, alternative-host finder and the sandbox payment gateway.

| Req | Priority | What it says (short) | Proven by |
|-----|----------|----------------------|-----------|
| FR-01 | Must | Register with email and password | `AuthTest::test_tourist_can_register_and_is_logged_in`, `AuthTest::test_registration_validates_input`, `AuthTest::test_nobody_can_register_as_admin` |
| FR-02 | Must | Log in, log out, reset password | `AuthTest::test_login_logout_flow`, `AuthTest::test_password_reset_flow`, `AuthTest::test_login_is_throttled_after_five_failures` |
| FR-03 | Must | Create and update role-specific profiles | `RbacAndProfileTest::test_tourist_updates_profile_with_languages_and_interests`, `RbacAndProfileTest::test_host_profile_is_created_pending_and_status_cannot_be_forged` |
| FR-04 | Should | Control visibility of private profile data | `AcceptanceTest::test_at_fr_04_a_user_controls_whether_the_phone_is_shared`, `RbacAndProfileTest::test_contact_details_are_hidden_until_the_booking_is_confirmed` |
| FR-05 | Must | Host submits identity evidence and a video call | `VerificationFlowTest::test_host_uploads_evidence_encrypted_and_only_once`, `VerificationFlowTest::test_admin_full_review_flow_verifies_the_host` |
| FR-06 | Must | Status is Pending, Verified, Rejected or Suspended | `VerificationFlowTest::test_state_table_allows_only_documented_moves`, `VerificationFlowTest::test_status_service_requires_reason_and_writes_audit` |
| FR-07 | Must | Only Verified hosts can be booked | `VerificationFlowTest::test_only_a_verified_host_is_bookable`, `BookingFlowTest::test_request_rules`, `SearchAndMatchingTest::test_unverified_hosts_and_unpublished_experiences_never_appear` |
| FR-08 | Must | Search and filter by city, language, interests, rating, price | `SearchAndMatchingTest::test_each_filter_works_alone_and_combined`, `SearchAndMatchingTest::test_date_and_group_size_filters` |
| FR-09 | Must | Recommendations from preferences and host data | `SearchAndMatchingTest::test_ranking_uses_the_weights_and_is_deterministic`, `SearchAndMatchingTest::test_weights_come_from_config` |
| FR-10 | Must | Human-readable reason for each recommendation | `SearchAndMatchingTest::test_every_result_has_a_reason_and_the_two_strongest_are_shown` |
| FR-11 | Must | Hosts publish available dates and slots | `ExperienceManagementTest::test_weekly_slots_blocked_dates_and_the_checker` |
| FR-12 | Must | No overlapping confirmed bookings | `BookingFlowTest::test_overlap_is_checked_among_confirmed_bookings_with_half_open_intervals`, `BookingConcurrencyTest::test_overlapping_requests_accepted_at_once_never_both_confirm` |
| FR-13 | Must | At most two confirmed bookings per day | `BookingFlowTest::test_at_most_two_confirmed_bookings_per_day`, `BookingConcurrencyTest::test_at_most_two_confirmed_per_day_under_parallel_accepts` |
| FR-14 | Must | Booking request with host, date, time, location, price | `BookingFlowTest::test_request_creates_requested_booking_with_authorised_payment`, `AcceptanceTest::test_at_main_journey_from_registration_to_late_host_cancellation` |
| FR-15 | Must | Confirm only after successful payment | `BookingFlowTest::test_booking_is_never_confirmed_when_capture_fails_or_times_out`, `BookingFlowTest::test_accept_captures_payment_then_confirms` |
| FR-16 | Must | Booking and payment status visible to tourist, host, admin | `BookingFlowTest::test_web_flow_request_accept_and_role_based_views`, `AcceptanceTest::test_at_fr_16_only_admins_may_open_the_booking_overview` |
| FR-17 | Must | Late host cancellation: full refund and an alternative host | `CancellationAndJobsTest::test_cancelling_19_hours_before_refunds_fully_and_suggests_hosts`, `CancellationAndJobsTest::test_finder_returns_same_city_verified_free_hosts_best_first_capped_at_three` |
| FR-18 | Must | In-app messages between tourist and host | `MessagingTest::test_only_the_two_participants_can_read_or_write`, `MessagingTest::test_conversation_exists_from_requested_and_closes_for_writing_when_the_booking_is_closed` |
| FR-19 | Must | Translated messages, original kept | `MessagingTest::test_tourist_and_host_converse_with_original_kept_and_translation_added`, `MessagingTest::test_translation_failure_never_blocks_or_loses_a_message` |
| FR-20 | Should | Notifications for in-scope events | `NotificationTest::test_each_in_scope_event_notifies_the_right_user`, `NotificationTest::test_a_failing_mail_provider_never_breaks_the_original_action` |
| NFR-02 | - | Filtered search within 2 s | `SearchAndMatchingTest::test_query_count_is_constant_and_300_hosts_search_fast` (load numbers: `docs/performance.md`) |
| NFR-09 | - | Salted one-way password hashing | `AuthTest::test_tourist_can_register_and_is_logged_in` (asserts the stored value is not the password) |
| NFR-10 | - | Role-based access | `SecurityTest::test_every_route_is_classified_and_protected_accordingly`, `RbacAndProfileTest::test_every_gate_ability_follows_the_matrix` |
| NFR-11 | - | No card data stored | `SecurityTest::test_no_card_data_is_stored_anywhere` |
| NFR-14 | - | No double booking under concurrency | `BookingConcurrencyTest::test_overlapping_requests_accepted_at_once_never_both_confirm` |
| NFR-18 | - | English, French and Arabic | `LanguageParityTest::test_every_file_and_key_exists_in_all_three_languages` |
| NFR-19 | - | Actor and timestamp for status changes (append-only) | `UsabilityTest::test_audit_rows_are_append_only`, `UsabilityTest::test_audit_rows_cannot_be_deleted`, `PaymentEngineTest::test_authorise_then_capture_writes_status_and_audit`, `VerificationFlowTest::test_status_service_requires_reason_and_writes_audit` |
| NFR-12 | - | Collect and expose only needed personal data | `UsabilityTest::test_every_account_action_lands_in_the_admin_timeline`, `RbacAndProfileTest::test_contact_details_are_hidden_until_the_booking_is_confirmed` |
| NFR-20 | - | Clear status when translation or payment is unavailable | `UsabilityTest::test_without_a_translation_service_the_original_is_shown_with_a_clear_calm_status`, `MessagingTest::test_translation_failure_never_blocks_or_loses_a_message` |

Not covered by an automated test (see the setup report for why): NFR-01, NFR-03 and NFR-04 timings on a deployed system, NFR-15 backup targets (restore drill in `docs/runbook.md`), NFR-16 browsers, NFR-17 and NFR-21 legal review.
