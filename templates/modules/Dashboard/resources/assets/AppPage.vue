<template>
  <div class="grid-full-height">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $t("module_dashboard.title") }}</h1>
    </section>

    <section class="content-header">
    </section>
    <!-- /.content -->

  </div>
</template>

<script type="javascript">
  import { loadModuleLanguage } from 'resources/assets/js/commons/LanguageHelper';

  let vueConfig = {
    mixins: [
      loadModuleLanguage('module_dashboard')
    ],
    components: {},
    data: function () {
      return {};
    },
    mounted: function () {
    },
    methods: {}
  };
  export default vueConfig;

</script>

<style lang="scss">
</style>
