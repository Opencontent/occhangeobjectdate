<div class="element">

    <table class="list">
        <tr>
            <th>Publish date</th>
        </tr>
        <tr>
            <td>
                {def $class=false()}
                {def $attribute=false()}
                {foreach $event.content.publish_class_array as $index => $class_id sequence array(bglight,bgdark) as $sequence}
                    {set $class=fetch('content', 'class', hash('class_id', $class_id))}
                    {set $attribute=fetch('content', 'class_attribute', hash('attribute_id', $event.content.publish_attribute_array[$index],
                    'version_id', 0))}
                    {$class.name|wash(xhtml)} / {$attribute.name|wash(xhtml)}
                    <br/>
                {/foreach}
            </td>
        </tr>
    </table>

    {def $past_state = $event.content.past_date_state}
    {if $past_state}
    <br />
    <table class="list">
        <tr>
            <th>{"Set status"|i18n("occhangeobjectdate/eventtype/edit")}</th>
        </tr>
        <tr>
            <td>{$past_state.current_translation.name|wash()}</td>
        </tr>
    </table>
    {/if}
    {def $future_state = $event.content.future_date_state}
    {if $future_state}
    <table class="list">
        <tr>
            <th>{"Set status if the publish date is in the future"|i18n("occhangeobjectdate/eventtype/edit")}</th>
        </tr>
        <tr>
            <td>{$future_state.current_translation.name|wash()}</td>
        </tr>
    </table>
    {/if}

    {undef $class $attribute $past_state $future_state}

</div>
