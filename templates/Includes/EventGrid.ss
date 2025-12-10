<div class="grid-x grid-padding-x grid-padding-y large-up-3">
    <% loop $EventList.Sort(SortOrder) %>
        <div class="element__Events__item cell">
            <% include EventHozCard %>
        </div>
    <% end_loop %>
</div>