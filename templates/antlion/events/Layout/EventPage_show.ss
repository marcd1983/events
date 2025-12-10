<div class="main grid-container $PageWidth" role="main">
<% if $Event %>
<div class="Event-detail">
  <nav class="grid-x grid-padding-x grid-padding-y align-right">
    <div class="cell shrink">
        <a class="button hollow tiny" href="$Top.Link">&larr; Back to $Top.Title.XML</a>
    </div>
  </nav>
  <div class="grid-x grid-padding-x grid-padding-y">
    <% if not $Event.HideImage %>
    <div class="cell large-4">
      <% if $Event.Image %>
        <figure class="Event-detail__figure">
          <img
            src="$Event.Image.FocusFill(800,800).URL"
            alt="$Event.Image.Title.ATT"
            loading="lazy" />
        </figure>
      <% end_if %>   
    </div>
    <% end_if %>
    <div class="cell auto">
      <header class="Event-header">
        <h1 class="Event-title">$Event.Title.XML</h1>
        <%-- Optional date window if your Event has StartDate/EndDate --%>
        <%-- <% if $Event.StartDate || $Event.EndDate %>
          <p class="Event-dates">
            <% if $Event.StartDate %>$Event.StartDate.Nice<% end_if %>
            <% if $Event.StartDate && $Event.EndDate %> &ndash; <% end_if %>
            <% if $Event.EndDate %>$Event.EndDate.Nice<% end_if %>
          </p>
        <% end_if %> --%>
		<% if EventTimeDisplay %>
			<div class="dates">
				$EventTimeDisplay 
			</div>
		<% end_if %>
		<% if $Time %><div class="time">Time: $Time &nbsp;</div><% end_if %>
		<% if $Location %><div class="locale">Location: $Location</div><% end_if %>   
      </header>

      <div class="Event-content">
        $Event.Content
      </div>

      <%-- Optional CTA buttons (if using LinkField / MultiLinkField as $Event.Links) --%>
      <% if $Event.Links.Exists %>
          <div class="button-group <% if $Align == 'center' %>align-center<% else_if $Align == 'right' %>align-right<% else %>align-left<% end_if %>">
            <% loop $Event.Links %>
             <a class="button $CssClass" href="$URL" <% if $OpenInNew %>target="_blank" rel="noopener noreferrer"<% end_if %>>$Title.XML</a>
            <% end_loop %>
          </div>
        <% end_if %>
    </div>
  </div>
  <div class="grid-x grid-padding-x grid-padding-y">
    <div class="cell">
      <div class="Event-content">
        <%-- $Event.Content --%>
        $Event.ElementalArea
      </div>
    </div>
  </div>

  <div class="grid-x grid-padding-x grid-padding-y">
    <div class="cell">
      <div class="Event-enquiry">
        <h3>Ask about this Event</h3>
          <% include FormMessageToast %>
          $EventForm
      </div>
    </div>
  </div>

</div>
<% else %>
  <%-- Fallback (usually not hit because controller 404s when missing) --%>
  <div class="grid-x grid-padding-x grid-padding-y">
    <div class="cell">

      <div class="callout text-center">

      <div class="toast toast--success toast--auto-hide callout alert p-40 text-center">

         <p>Sorry, we couldn’t find that Eventtion.</p>
        <a class="button hollow" href="$Top.Link">&larr; Back to $Top.Title.XML</a>
      </div>
    </div>
  </div>
<% end_if %>
</div>