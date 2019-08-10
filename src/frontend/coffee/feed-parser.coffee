
Vue.prototype.$delay = (ms, cb) ->
    setTimeout cb, ms
    on

Vue.prototype.$axios = axios.create
    baseURL: '/fid-parser/api/'
    responseType: 'json'

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-loader',
    template: '
        <div
            class="loader"
            :class="{ active: isLoading }"
        >
            <img
                class="loader-img"
                height="8"
                src="/img/loader.svg"
                v-show="!isError"
            >
            <p
                class="loader-error"
                v-show="isError"
            >
                <i class="fas fa-exclamation-triangle"></i>
                <span v-html="msg"></span>
            </p>
        </div>
    '

    data: ->
        isLoading: off
        isError: off
        msg: '&nbsp;'
        callBack: (data) ->

    methods:
        post: (url, data, cb) ->
            return off if @isLoading
            @isLoading = on
            @callBack = cb
            @$axios
                method: 'post'
                url: url
                data: data
            .then (response) =>
                @isLoading = off
                @$delay 160, () =>
                    @isError = off
                    @msg = '&nbsp;'
                @callBack response.data
            .catch (error) =>
                @msg = error.response
                @isError = on
                @$delay 3000, () =>
                    @isLoading = off
                @$delay 3160, () =>
                    @isError = off
                    @msg = '&nbsp;'
            .then () =>
            on

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

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

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-btn-group',
    template: '
        <div>
            <span class="label">Добавить ссылку на фид:</span>
            <div class="btn-group">
                <app-button
                    v-for="(button, i) in buttons"
                    :key="i"
                    :button="button"
                    @click="click"
                />
            </div><!-- /.btn-group -->
        </div>
    '

    props:
        buttons:
            type: Array
            required: on

    methods:
        click: (button) ->
            this.$emit 'click', button

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-input',
    # value="http://crm.loc/tmp/yandex.xml"
    template: '
        <div
            class="animated faster"
            :class="animClass"
        >
            <label class="label">{{ input.label }}</label>
            <div class="input-group">
                <input
                    type="text"
                    class="input"
                    :class="{error: error}"
                    :name="input.name"
                    @focus="error = false"
                >
                <a
                    href="#"
                    class="input-group-right ln-red"
                    @click.prevent="remove"
                >
                    <i class="fas fa-times"></i>
                </a>
            </div><!-- /.input-group -->
            <span v-if="error" class="input-error">{{ error }}</span>
        </div>
    '

    props:
        input:
            type: Object
            required: on

    data: ->
        error: off
        animClass: 'fadeInDown'

    methods:
        remove: (e) ->
            this.$emit 'remove', this
            on

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-input-list',
    template: '
        <div class="d-flex flex-column-reverse">
            <app-input
                v-for="(input, i) in inputs"
                :key="input.id"
                :input="input"
                @remove="removeInput($event, i)"
            />
        </div><!-- /.d-flex -->
    '

    props:
        inputs:
            type: Array
            required: on

    methods:
        removeInput: (input, i) ->
            this.$emit 'remove', input, i
            on

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-form',
    template: '
        <div class="wall relative">
            <app-btn-group :buttons="buttons" @click="addInput"/>
            <form @submit.prevent="send">
                <app-input-list
                    :inputs="inputs"
                    @remove="removeInput"
                />
                <div
                    class="row animated faster"
                    :class="submitAnimClass"
                    v-show="showSubmit"
                >
                    <div class="col-auto ml-auto">
                        <button class="btn btn-green" type="submit">Парсинг</button>
                    </div>
                </div><!-- /.row -->
            </form>
            <app-loader ref="loader"/>
        </div><!-- /.wall -->
    '

    data: ->
        inputs: []
        inputId: 0
        submitAnimClass: ''
        showSubmit: off

    methods:
        addInput: (input) ->
            this.inputId = 0 if this.inputs.length is 0
            id = ++this.inputId
            this.inputs.push
                label: input.label
                name: "#{input.name}[#{id}]"
                id: id
            this.toggleSubmit on if this.inputs.length is 1
            on
        removeInput: (input, i) ->
            this.toggleSubmit off if this.inputs.length is 1
            input.animClass = 'fadeOutUp'
            this.$delay 500, () => this.inputs.splice i, 1
            on
        toggleSubmit: (show) ->
            if show
                this.submitAnimClass = 'fadeInUp'
                this.showSubmit = on
            else
                this.submitAnimClass = 'fadeOutDown'
                this.$delay 500, () => this.showSubmit = off
            on
        send: (e) ->
            formData = new FormData e.target
            this.$refs.loader.post 'parse.json', formData, (data) =>
                if data?.errors?
                    console.log data.errors
                    # this.addErrors Number(id), msgArr[0] for id, msgArr of data.errors
                else if data?.xlsxFiles?
                    @$emit 'complete', data.xlsxFiles
            on

    computed:
        buttons: ->
            [
                { label: 'Yandex', name: 'yandex' }
                { label: 'Avito', name: 'avito' }
                { label: 'Cian', name: 'cian' }
            ]

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-row',
    template: '
        <div
            class="row-view animated faster"
            :class="animClass"
        >
            <div class="row-view-content">
                <p class="text">{{ file.name }}</p>
                <p class="text red">{{ error }}</p>
            </div><!-- /.row-view-content -->
            <div class="action">
                <a
                    class="action-btn delete"
                    href="#"
                    @click.prevent="remove"
                >
                    <i class="fas fa-trash-alt"></i>
                </a>
                <a
                    class="action-btn view"
                    target="_blank"
                    download
                    :href="file.url"
                >
                    <i class="fas fa-download"></i>
                </a>
            </div><!-- /.action -->
            <app-loader ref="loader"/>
        </div><!-- /.row-view -->
    '

    props:
        file: Object # {name, url}
        required: on

    data: ->
        animClass: ''
        error: ''

    methods:
        remove: (e) ->
            formData = new FormData
            formData.set 'xlsxFileName', @file.name
            @$refs.loader.post 'remove-xlsx.json', formData, (data) =>
                if data?.errors?.xlsxFileName?
                    @error = data.errors.xlsxFileName[0]
                    @$delay 3000, => @error = ''
                else if data?.status? and data.status is true
                    @animClass = 'zoomOut'
                    @$delay 500, => @$emit 'remove'
                on

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-row-list',
    template: '
        <div class="relative">
            <p v-show="files.length" class="text">Excel - спарсенные данные</p>
            <app-row
                v-for="(file, i) in files"
                :file="file"
                :key="file.id"
                @remove="removeFile(i)"
            />
            <app-loader ref="loader"/>
        </div>
    '

    props:
        files:
            type: Array
            required: on


    methods:
        removeFile: (i) ->
            @$emit 'remove', i
            on

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

App =
    template: '
        <div class="container">
            <app-form
                @complete="updateXlsx"
            />
            <app-row-list
                :files="xlsxFiles"
                @remove="removeFile"
            />
        </div><!-- /.container -->
    '

    data: ->
        xlsxFiles: xlsxFiles ? []

    methods:
        updateXlsx: (xlsxFiles) ->
            @xlsxFiles = xlsxFiles
            on
        removeFile: (i) ->
            @xlsxFiles.splice i, 1
            on

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

new Vue
    render: (h) ->
        h App
.$mount '#app-fid-parser-index'
