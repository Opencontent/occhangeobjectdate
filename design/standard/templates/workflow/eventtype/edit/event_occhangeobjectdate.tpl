{def $base='WorkflowEvent'
     $publish_id_array=$event.content.publish_id_array}
<div class="block">
<div class="element">
    {def $possibleClasses=$event.workflow_type.class_attributes}
    <legend>{"Publish attributes"|i18n("occhangeobjectdate/eventtype/edit")}</legend>
    {*
    <select name="{$base}_data_changeobjectdate_attribute_{$event.id}[]" size="10" multiple="multiple">
    {foreach $possibleClasses as $class_attribute}
        <option value="{$class_attribute.id}"{if $publish_id_array|contains($class_attribute.id)} selected="selected"{/if}>{$class_attribute.class.name|wash(xhtml)} / {$class_attribute.class_attribute.name|wash(xhtml)}</option>
    {/foreach}
    </select>
    *}
    {def $current_class = false()}
    {foreach $possibleClasses as $class_attribute}        
    <label>
        <input type="checkbox" name="{$base}_data_changeobjectdate_attribute_{$event.id}[]" value="{$class_attribute.id}"{if $publish_id_array|contains($class_attribute.id)} checked="checked"{/if}>{$class_attribute.class.name|wash(xhtml)} / {$class_attribute.class_attribute.name|wash(xhtml)}
    </label>    
    {/foreach}

<input type="hidden" name="{$base}_data_changeobjectdate_do_update_{$event.id}" value="1" />    
</div>
<div class="break"></div>
</div>


