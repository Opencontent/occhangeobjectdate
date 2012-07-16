<div class="element">

{section show=$event.content.modify_date}
{'Publish date will be modified.'|i18n('occhangeobjectdate/design/standard/workflow/eventtype/view')}
{section-else}
{'Publish date will not be modified.'|i18n('occhangeobjectdate/design/standard/workflow/eventtype/view')}
{/section}

<table class="list">
<tr>
    <th>Publish date</th>
</tr>
<tr>
    <td class="{$sequence}">
    {def $class=false()}
    {def $attribute=false()}
    {foreach $event.content.publish_class_array as $index => $class_id sequence array(bglight,bgdark) as $sequence}
    {set $class=fetch('content', 'class', hash('class_id', $class_id))}
        {set $attribute=fetch('content', 'class_attribute', hash('attribute_id', $event.content.publish_attribute_array[$index],
                                                                 'version_id', 0))}
    {$class.name|wash(xhtml)} / {$attribute.name|wash(xhtml)} <br />
    {/foreach}
    </td>
</tr>
</table>

</div>
