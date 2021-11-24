<!-- Object Expires -->
<div id="ml_links_right_docs_obj_expires_title" style="display: none;">
    Object Expires
</div>
<div id="ml_links_right_docs_obj_expires_content" style="display: none;">
    Specify link object's expiration date; for example, if a campaign is being run, then you may wish to terminate after
    3 months.
    <br><br>
    Alternatively, select the Never Expires options if campaign is to be kept alive indefinitely.
</div>
<!-- Object Expires -->

<!-- Magic Link Type -->
<div id="ml_links_right_docs_magic_link_type_title" style="display: none;">
    Magic Link Type
</div>
<div id="ml_links_right_docs_magic_link_type_content" style="display: none;">
    Select magic link type and template to be associated with link object settings; which will be displayed to assigned
    users following scheduled message dispatches.
    <br><br>
    Once selected, available fields to be displayed within Magic Link Type Fields section.
</div>
<!-- Magic Link Type -->

<!-- Assigned Users & Teams -->
<div id="ml_links_right_docs_assign_users_teams_title" style="display: none;">
    Assigned Users & Teams
</div>
<div id="ml_links_right_docs_assign_users_teams_content" style="display: none;">
    Select users and team members to be assigned to current link object settings.
    <br><br>
    Invalid phone numbers and email addresses will be highlighted in red and are to be manually corrected so as to avoid
    any errors whilst trying to send messages.
    <br><br>
    Phone numbers must be formatted in the E.164 standard: [+] [country code] [subscriber number including area code]
    and can have a maximum of fifteen digits. For example: +14155552671
</div>
<!-- Assigned Users & Teams -->

<!-- Message -->
<div id="ml_links_right_docs_message_title" style="display: none;">
    Message
</div>
<div id="ml_links_right_docs_message_content" style="display: none;">
    Specify text to be used when sending messages. <br><br>
    The message content can support free-form plain text, with the use of placeholders; which will be replaced during
    message dispatch. <br><br>The following placeholders are currently supported:<br>
    <hr>

    <b>{{name}}</b><br>Will be replaced with either user's display name, or D.T. post record title.<br><br>
    <b>{{link}}</b><br>Will be replaced with magic link.<br><br>
    <b>{{time}}</b><br>Will be replaced with expiry time of magic link.
</div>
<!-- Message -->

<!-- Frequency -->
<div id="ml_links_right_docs_frequency_title" style="display: none;">
    Frequency
</div>
<div id="ml_links_right_docs_frequency_content" style="display: none;">
    If scheduling has been enabled, specify how often messages are to be dispatched.
</div>
<!-- Frequency -->

<!-- Sending Channel -->
<div id="ml_links_right_docs_send_channel_title" style="display: none;">
    Sending Channel
</div>
<div id="ml_links_right_docs_send_channel_content" style="display: none;">
    Select sending channel to be used, in order to dispatch messages to assigned users and team members.
</div>
<!-- Sending Channel -->

<!-- Links Expire Within -->
<div id="ml_links_right_docs_links_expire_title" style="display: none;">
    Links Expire Within
</div>
<div id="ml_links_right_docs_links_expire_content" style="display: none;">
    Specify when actual DT user magic links will expire and are no longer accessible.
    <br><br>
    Alternatively, select the Never Expires options if DT user magic links are to be kept alive indefinitely.
    <br><br>
    This setting is relative to a base timestamp; which is updated during any of the following events:
    <ul>
        <li>1. When a link object is newly created.</li>
        <li>2. When auto refresh is enabled and executed during a scheduled run.</li>
        <li>3. When a manual refresh is triggered for all assigned user links.</li>
        <li>4. When a Send Now request is triggered.</li>
    </ul>
</div>
<!-- Links Expire Within -->

<!-- Links Expire On -->
<div id="ml_links_right_docs_links_expire_on_title" style="display: none;">
    Links Expire On
</div>
<div id="ml_links_right_docs_links_expire_on_content" style="display: none;">
    Anticipated date when current DT user magic links will expire and will no longer be accessible.
</div>
<!-- Links Expire On -->

<!-- Links Expiry Auto-Refresh Enabled -->
<div id="ml_links_right_docs_auto_refresh_title" style="display: none;">
    Links Expiry Auto-Refresh Enabled
</div>
<div id="ml_links_right_docs_auto_refresh_content" style="display: none;">
    If DT user magic links have been scheduled to expire, then enabling auto-refresh will ensure new links are generated
    on expiration.
    <br><br>
    Alternatively, disabling auto-refresh will ensure expired DT user magic links remain inaccessible.
</div>
<!-- Links Expiry Auto-Refresh Enabled -->
