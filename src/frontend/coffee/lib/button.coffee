Vue.component 'app-button',
    template: '
        <div class="col-12 col-sm">
            <button
                class="btn btn-default"
                @click.prevent="click"
            >{{ button.label }}</button>
        </div><!-- /.col -->
    '

    props:
        button:
            type: Object
            required: on

    methods:
        click: (e) ->
            this.$emit 'click', this.button
            on