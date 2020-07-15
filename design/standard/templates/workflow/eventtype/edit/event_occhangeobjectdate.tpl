{def $base='WorkflowEvent'
     $publish_id_array=$event.content.publish_id_array
     $past_state = $event.content.past_date_state_id
     $future_state = $event.content.future_date_state_id}
<div class="block">
    <div class="element">
        {def $possibleClasses=$event.workflow_type.class_attributes}
        <legend>{"Publish attributes"|i18n("occhangeobjectdate/eventtype/edit")}</legend>
        {def $current_class = false()}
        {foreach $possibleClasses as $class_attribute}
            <label>
                <input type="checkbox" name="{$base}_data_changeobjectdate_attribute_{$event.id}[]"
                       value="{$class_attribute.id}"
                        {if $publish_id_array|contains($class_attribute.id)} checked="checked"{/if} />
                {$class_attribute.class.name|wash(xhtml)}/{$class_attribute.class_attribute.name|wash(xhtml)}
            </label>
        {/foreach}

        <input type="hidden" name="{$base}_data_changeobjectdate_do_update_{$event.id}" value="1"/>
    </div>
    <div class="break"></div>
</div>

{def $state_list = object_state_list()}
{if $state_list}
<div class="block">
    <label for="paststate">{"Set status"|i18n("occhangeobjectdate/eventtype/edit")}</label>
    <select id="paststate" class="element" name="past_date_state_id">
        <option></option>
        {foreach $state_list as $id => $name}
            <option value="{$id}" {if $past_state|eq($id)}selected="selected"{/if}>{$name|wash()}</option>
        {/foreach}
    </select>
</div>
<div class="block">
    <label for="futurestate">{"Set status if the publish date is in the future"|i18n("occhangeobjectdate/eventtype/edit")}</label>
    <select id="futurestate" class="element" name="future_date_state_id">
        <option></option>
        {foreach $state_list as $id => $name}
            <option value="{$id}" {if $future_state|eq($id)}selected="selected"{/if}>{$name|wash()}</option>
        {/foreach}
    </select>
</div>
{/if}
{undef $state_list}


