
delay = (ms, cb) ->
    setTimeout cb, ms

class Loader

    axios: {}

    _i: 0

    _callBackSuccess: (data) ->
    _callBackError: (message) ->
    _callBackAllways: () ->

    _callBackPartSuccess: (data) ->
    _callBackPartError: (message) ->
    _callBackPartAllways: () ->
    _callBackPartAllDone: () ->

    constructor: () ->
        @axios = axios.create
            baseURL: '/fid-parser/api/'
            responseType: 'json'

    post: (action, data) =>
        @axios
            method: 'post'
            url: action
            data: data
        .then (response) =>
            @_callBackSuccess response.data
            on
        .catch (error) =>
            msg = 'Внутренняя ошибка системы';
            if error.response?.data?.message? and error.response.data.message isnt ''
                msg = error.response.data.message
            @_callBackError msg
            on
        .then () =>
            @_callBackAllways()
            on
        @

    postEach: (action, dataArr, i=0) =>
        isAllDone = dataArr.length is (i + 1)
        isAllDone = on unless dataArr.length
        @axios
            method: 'post'
            url: action
            data: dataArr[i]
        .then (response) =>
            @_callBackPartSuccess response.data, i
            @_callBackSuccess null if isAllDone
            on
        .catch (error) =>
            msg = 'Внутренняя ошибка системы';
            if error.response?.data?.message? and error.response.data.message isnt ''
                msg = error.response.data.message
            @_callBackPartError msg, i
            on
        .then () =>
            @_callBackPartAllways()
            if isAllDone
                @_callBackPartAllDone()
            else
                @postEach action, dataArr, (i + 1)
            on
        @

    success: (callBack) =>
        @_callBackSuccess = callBack
        @

    error: (callBack) =>
        @_callBackError = callBack
        @

    allways: (callBack) =>
        @_callBackAllways = callBack
        @

    partSuccess: (callBack) =>
        @_callBackPartSuccess = callBack
        @

    partError: (callBack) =>
        @_callBackPartError = callBack
        @

    partAllways: (callBack) =>
        @_callBackPartAllways = callBack
        @

    partAllDone: (callBack) =>
        @_callBackPartAllDone = callBack
        @

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.config.productionTip = off

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-xml-xlsx',
    template: '
        <div>
            <div class="wall relative">
                <span class="label">Добавить ссылку на фид (xml):</span>
                <div class="btn-group">
                    <div class="col-12 col-sm" v-for="(button, i) in buttons" :key="i">
                        <button class="btn btn-default" @click.prevent="addInput(button)">
                            {{ button.label }}
                        </button>
                    </div><!-- /.col -->
                </div><!-- /.btn-group -->
                <div class="d-flex flex-column-reverse">
                    <div v-for="(input, i) in inputs">
                        <label class="label">{{ input.label }}</label>
                        <div class="input-group">
                            <input
                                type="text"
                                class="input"
                                autocomplete="off"
                                v-model.trim="input.val"
                                :class="{error: input.error}"
                                @focus="input.error = false"
                            >
                            <a class="input-group-right ln-red" href="#" @click.prevent="removeInput(i)">
                                <i class="fas fa-times"></i>
                            </a>
                        </div><!-- /.input-group -->
                        <span v-if="input.error" class="input-error">{{ input.error }}</span>
                    </div><!-- v-for inputs -->
                </div><!-- /.d-flex -->
                <div class="row" v-if="showSubmit">
                    <div class="col-auto ml-auto">
                        <a class="btn btn-green" href @click.prevent="send">Парсинг</a>
                    </div>
                </div><!-- /.row -->
            </div><!-- /.wall -->
            <p v-show="files.length" class="text">Excel - спарсенные данные</p>
            <div class="row-view" v-for="(file, i) in files">
                <div class="row-view-content">
                    <p class="text">{{ file.name }}</p>
                </div><!-- /.row-view-content -->
                <div class="action">
                    <a class="action-btn delete" href="#" @click.prevent="removeFile(file, i)">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <a class="action-btn view" target="_blank" download :href="file.url">
                        <i class="fas fa-download"></i>
                    </a>
                </div><!-- /.action -->
                <div class="loader" :class="{ active: file.loader.active }">
                    <img class="loader-img" height="6" src="/img/loader.svg" v-if="!file.loader.error">
                    <p class="loader-error" v-if="file.loader.error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span v-html="file.loader.error"></span>
                    </p>
                </div>
            </div><!-- /.row-view -->
            <div class="loader fixed" :class="{ active: loader.active }">
                <img class="loader-img" height="6" src="/img/loader.svg">
                <p class="loader-info" v-if="loader.info">
                    <span v-html="loader.info"></span>
                </p>
                <p class="loader-error" v-if="loader.error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span v-html="loader.error"></span>
                </p>
            </div>
        </div><!-- /.root -->
    '

    created: ->
        @addXlsxFiles()
        on

    data: ->
        files: []
        inputs: []
        showSubmit: off
        loader:
            active: off
            info: ''
            error: ''

    methods:
        addInput: (button) ->
            @inputs.push
                label: button.label
                name: button.name
                error: off
                val: ''
            @showSubmit = on if @inputs.length is 1
            on
        removeInput: (i) ->
            @showSubmit = off if @inputs.length is 1
            @inputs.splice i, 1
            on
        send: (e) ->
            @loader.active = on
            dataArr = for input in @inputs
                "#{input.name}XmlUrl": input.val
            loader = new Loader
            loader.postEach 'parse-xml.json', dataArr
            .partSuccess (data, i) =>
                if data?.errors?.processErrors?
                    @inputs[i].error = data.errors.processErrors[0]
                else if data?.xlsxFiles?
                    @addXlsxFiles data.xlsxFiles
                @loader.info = "Обработано #{i+1} из #{@inputs.length}"
                on
            .partError (msg, i) =>
                @inputs[i].error = msg
                on
            .partAllDone () =>
                @loader.active = off
                @loader.info = ''
                @loader.error = ''
                @inputs = @inputs.filter (input) -> if input.error then on else off
                @showSubmit = off if @inputs.length is 0
                on
            on
        removeFile: (file, i) ->
            file.loader.active = on
            loader = new Loader
            loader.post 'remove-xlsx.json', 'xlsxFileName': file.name
            .success (data) =>
                if data?.errors?.xlsxFileName?
                    file.loader.error = data.errors.xlsxFileName[0]
                    delay 3000, =>
                        file.loader.active = off
                        file.loader.error = ''
                    on
                else if data?.status? and data.status is on
                    @files.splice i, 1
                    @addXlsxFiles @files
                    on
                on
            .error (msg) =>
                file.loader.error = msg
                delay 3000, =>
                    file.loader.active = off
                    file.loader.error = ''
                on
            on
        addXlsxFiles: (newXlsxFiles=off) ->
            window.xlsxFiles = newXlsxFiles if newXlsxFiles
            if window.xlsxFiles? and window.xlsxFiles.length
                @files = for file in window.xlsxFiles
                    file.loader =
                        active: off
                        error: ''
                    file
                return on
            @files = []
            on



    computed:
        buttons: ->
            [
                { label: 'Yandex', name: 'yandex' }
                { label: 'Avito', name: 'avito' }
                { label: 'Cian', name: 'cian' }
            ]

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

Vue.component 'app-xlsx-xml',
    template: '
        <div>
            <div class="wall relative">
                <span class="label">Добавить файл Excel (xlsx):</span>
                <div class="btn-group">
                    <div class="col-12 col-sm" v-for="(button, i) in buttons" :key="i">
                        <button class="btn btn-default" @click.prevent="addInput(button)">
                            {{ button.label }}
                        </button>
                    </div><!-- /.col -->
                </div><!-- /.btn-group -->
                <div v-for="(input, i) in inputs">
                    <label class="label">{{ input.label }}</label>
                    <div class="input-group">
                        <div class="input" :class="{error: input.error}">
                            {{ input.fileName }}
                        </div>
                        <a class="input-group-right ln-red" href="#" @click.prevent="removeInput(i)">
                            <i class="fas fa-times"></i>
                        </a>
                    </div><!-- /.input-group -->
                    <span v-if="input.error" class="input-error">{{ input.error }}</span>
                </div>
                <div class="row" v-if="showSubmit">
                    <div class="col-auto ml-auto">
                        <a class="btn btn-green" href @click.prevent="send">Конвертировать</a>
                    </div>
                </div><!-- /.row -->
            </div><!-- /.wall -->
            <p v-show="files.length" class="text">XML - конвертированные файлы</p>
            <div class="row-view" v-for="(file, i) in files">
                <div class="row-view-content">
                    <p class="text">{{ file.name }}</p>
                </div><!-- /.row-view-content -->
                <div class="action">
                    <a class="action-btn delete" href="#" @click.prevent="removeFile(file, i)">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <a class="action-btn update" target="_blank" download :href="file.url">
                        <i class="fas fa-download"></i>
                    </a>
                    <a class="action-btn view" target="_blank" :href="file.url">
                        <i class="fas fa-link"></i>
                    </a>
                </div><!-- /.action -->
                <div class="loader" :class="{ active: file.loader.active }">
                    <img class="loader-img" height="6" src="/img/loader.svg" v-if="!file.loader.error">
                    <p class="loader-error" v-if="file.loader.error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span v-html="file.loader.error"></span>
                    </p>
                </div>
            </div><!-- /.row-view -->
            <div class="loader fixed" :class="{ active: loader.active }">
                <img class="loader-img" height="6" src="/img/loader.svg">
                <p class="loader-info" v-if="loader.info"><span v-html="loader.info"></span></p>
                <p class="loader-error" v-if="loader.error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span v-html="loader.error"></span>
                </p>
            </div>
        </div><!-- /.root -->
    '

    created: ->
        @addXmlFiles()
        on

    data: ->
        files: []
        inputs: []
        showSubmit: off
        loader:
            active: off
            info: ''
            error: ''

    methods:
        addInput: (button) ->
            input = document.createElement 'input'
            input.type = 'file'
            input.accept = '.xlsx'
            input.onchange = (e) =>
                file = input.files[0]
                @inputs.push
                    label: button.label
                    fileName: file.name
                    error: ''
                    name: button.name
                    file: file
                @showSubmit = on if @inputs.length is 1
                on
            input.click()
            on
        removeInput: (i) ->
            @showSubmit = off if @inputs.length is 1
            @inputs.splice i, 1
            on
        addXmlFiles: (newXmlFiles=off) ->
            window.xmlFiles = newXmlFiles if newXmlFiles
            if window.xmlFiles? and window.xmlFiles.length
                @files = for file in window.xmlFiles
                    file.loader =
                        active: off
                        error: ''
                    file
                return on
            @files = []
            on
        send: (e) ->
            @loader.active = on
            dataArr = for input in @inputs
                formData = new FormData
                formData.set "#{input.name}XlsxFile", input.file
                formData
            loader = new Loader
            loader.postEach 'parse-xlsx.json', dataArr
            .partSuccess (data, i) =>
                if data?.errors?.processErrors?
                    @inputs[i].error = data.errors.processErrors[0]
                else if data?.xmlFiles?
                    @addXmlFiles data.xmlFiles
                @loader.info = "Обработано #{i+1} из #{@inputs.length}"
                on
            .partError (msg, i) =>
                @inputs[i].error = msg
                on
            .partAllDone () =>
                @loader.active = off
                @loader.info = ''
                @loader.error = ''
                @inputs = @inputs.filter (input) -> if input.error then on else off
                @showSubmit = off if @inputs.length is 0
                on
            on
        removeFile: (file, i) ->
            file.loader.active = on
            loader = new Loader
            loader.post 'remove-xml.json', 'xmlFileName': file.name
            .success (data) =>
                if data?.errors?.xmlFileName?
                    file.loader.error = data.errors.xmlFileName[0]
                    delay 3000, =>
                        file.loader.active = off
                        file.loader.error = ''
                    on
                else if data?.status? and data.status is on
                    @files.splice i, 1
                    @addXmlFiles @files
                    on
                on
            .error (msg) =>
                file.loader.error = msg
                delay 3000, =>
                    file.loader.active = off
                    file.loader.error = ''
                on
            on

    computed:
        buttons: ->
            [
                { label: 'Yandex', name: 'yandex' }
                { label: 'Avito', name: 'avito' }
                { label: 'Cian', name: 'cian' }
            ]

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

App =
    template: '
        <div class="container">
            <div class="tab">
                <a
                    class="tab-ln"
                    href="#"
                    v-for="tab in tabs"
                    :class="{ active: tab.active }"
                    @click.prevent="showComponent(tab.name)"
                    ><span v-html="tab.label"></span></a>
            </div>
            <component :is="currentComponent"></component>
        </div><!-- /.container -->
    '

    data: ->
        tabs: [
            {
                name: 'app-xml-xlsx'
                label: 'XML <i class="fas fa-chevron-right"></i> XLSX'
                active: off
            }
            {
                name: 'app-xlsx-xml'
                label: 'XLSX <i class="fas fa-chevron-right"></i> XML'
                active: on
            }
        ]
        currentComponent: 'app-xlsx-xml'

    methods:
        showComponent: (name) ->
            tab.active = name is tab.name for tab in @tabs
            @currentComponent = name

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

new Vue
    render: (h) -> h App
.$mount '#app-fid-parser-index'
