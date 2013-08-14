<?xml version="1.0"?>
<markers>
<% loop $locations %><marker name="{$Name}" address="{$Address}" lat="{$Latitude}" lng="{$Longitude}" distance="{$Distance}"/><% end_loop %>
</markers>