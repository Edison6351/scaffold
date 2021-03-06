<?php
/**
 * @var $STARTER  \zgldh\Scaffold\Installer\ModuleStarter
 * @var $MODEL  \zgldh\Scaffold\Installer\Model\ModelDefinition
 * @var $field  \zgldh\Scaffold\Installer\Model\FieldDefinition
 */
$modelSnakeCase = $MODEL->getSnakeCase();
$route = $MODEL->getRoute();
?>
@foreach($MODEL->getFields() as $field)
@php
  $htmlType = $field->getHtmlType();
@endphp
@if($field->getRelationship())
      {{$htmlType->getStoreActionName()}}:_.debounce(function (term) {
        return this.$store.dispatch('{{$htmlType->getStoreActionName()}}', term);
      }, 500),
@endif
@endforeach