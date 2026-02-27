<div class="grid-x grid-padding-x grid-padding-y large-up-3 small-up-2">
    <% loop $EventList.Sort(SortOrder) %>
        <div class="element__Events__item cell">
            <% include EventCard %>
        </div>
    <% end_loop %>
</div>