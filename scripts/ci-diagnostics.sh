#!/usr/bin/env bash
# Source in disposable CI steps; preserve failures and expose their final output.
cs_step_log="$(mktemp /tmp/codeslot-ci-step-XXXXXX.log)"
exec > >(tee "$cs_step_log") 2>&1
cs_report_failure() {
  local cs_exit_code="$1" cs_message
  cs_message="$(tail -n 25 "$cs_step_log")"
  cs_message="${cs_message//%/%25}"
  cs_message="${cs_message//$'\r'/%0D}"
  cs_message="${cs_message//$'\n'/%0A}"
  echo "::error title=WordPress runtime failure::$cs_message"
  return "$cs_exit_code"
}
trap 'cs_report_failure "$?"' ERR
