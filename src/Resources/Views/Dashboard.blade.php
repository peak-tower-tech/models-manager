<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <title>Model Dumper Dashboard</title>
    {{--    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet"--}}
    {{--          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">--}}
    {{--    --}}
    <link href="{{asset('vuejs/bootstrap.min.css')}}" rel="stylesheet"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    {{--    <script--}}
    {{--        src="https://code.jquery.com/jquery-3.3.1.min.js"--}}
    {{--        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="--}}
    {{--        crossorigin="anonymous"></script>--}}
    <script
        src="{{asset('vuejs/jquery-3.3.1.min.js')}}"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>
    {{--    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.min.js"></script>--}}
    {{--    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>--}}
    <script src="{{asset('vuejs/vue.js')}}"></script>


</head>

<body>
<div class="container" id="app">
    <div class="card col-xs-12 mt-4">
        <div class="card-header">
            <form id="manage" class="form-inline" role="form">
                <div class="col-3">
                    <div class="col-12">
                        <label class="my-1 mr-2" for="mainModelClass">Main Model Class To Duplicate:</label>
                        <select class="form-control form-control-sm mr-2" @change="mainModelClassChange()"
                                name="mainModelClass"
                                id="mainModelClass" v-model="mainModelClass">
                            <option v-for="(parentConfig,mainModelClass) in modelClasses"
                                    :value="mainModelClass">@{{
                                mainModelClass}}
                            </option>
                        </select>
                    </div>
                    <hr>
                    <div class="col-12" v-if="mainModelClass">

                        <label class="my-1 mr-2" for="mainModelId">Duplicate From Which Id :</label>
                        <input class="form-control form-control-sm mr-2" v-model="mainModelId"
                               placeholder="database id">
                    </div>
                    <div class="col-12 mt-3" v-if="mainModelClass">
                        <button v-if="mainModelId" type="submit" @click.prevent="getModelAndRelations"
                                class="mr-2 btn btn-sm btn-primary">
                            Get Model And Relations
                        </button>
                    </div>
                </div>

                <div class="col-6">
                    <div v-if="repository" class="mt-3">
                        <label class="my-1 mr-2" for="mainModelId">Choisen Relations For Duplicate :</label>
                        <div v-for="(attr,modellClass) in repository">
                            <input :id="modellClass" :value="modellClass"
                                   v-model="relations" type="checkbox"
                                   {{--                                       :disabled="selectedItems.length >= max && selectedItems.indexOf(index) == -1"--}}
                                   {{--                                       :disabled="attr[`config`][`dependsOn`].length > 0"--}}
                                   :disabled="
                                        attr[`config`]!=undefined &&
                                        attr[`config`][`dependsOn`]!=undefined &&
                                        attr[`config`][`dependsOn`].length > 0 &&
                                        attr[`config`][`dependsOn`].every(dependency => relations.indexOf(dependency) == -1)
                                    "
                            >
                            @{{ modellClass }} <i style="color: red">=> @{{ attr['count'] ?? 0 }} record</i><br/>
                        </div>
                    </div>
                </div>

                <div class="col-3" v-if="relations.length">
                    <div class="col-12">
                        <label class="my-1 mr-2" for="parentModelClass">Duplicate For Target Model Class :</label>
                        <select class="form-control form-control-sm mr-2"
                                name="parentModelClass"
                                id="parentModelClass" v-model="parentModelClass">
                            <option v-for="(parentConfig,mainModelClass) in modelClasses"
                                    :value="mainModelClass">@{{
                                mainModelClass}}
                            </option>
                        </select>

                    </div>
                    <hr/>
                    <div class="col-12" v-if="parentModelClass">
                        <label class="my-1 mr-2" for="parentTargetModelId">Duplicate For Target Model with Id :</label>
                        <input class="form-control form-control-sm mr-2" v-model="parentTargetModelId"
                               placeholder="database main id">
                    </div>
                    <div class="col-12">
                        <button v-if="parentTargetModelId" type="submit" @click.prevent="sendDuplicateRequest"
                                class="mr-2 btn btn-sm btn-success mt-3">
                            Duplicate
                        </button>
                    </div>

                </div>
                <div v-if="results">
                    <div v-for="modellClassname in results">
                        <span>@{{ modellClassname }}</span>
{{--                        <i style="color: green">@{{ results[modellClass].length }} duplicated</i>--}}
                        <br/>
                    </div>
                </div>

                {{--                <button v-if="connected" type="submit" @click.prevent="disconnect" class="btn btn-sm btn-danger">--}}
                {{--                    Disconnect--}}
                {{--                </button>--}}

            </form>
            <div id="status"></div>
        </div>
        {{--        <div class="card-body">--}}
        {{--            <div v-if="connected && app.statisticsEnabled">--}}
        {{--                <h4>Realtime Statistics</h4>--}}
        {{--                <div id="statisticsChart" style="width: 100%; height: 250px;"></div>--}}
        {{--            </div>--}}
        {{--            <div v-if="connected">--}}
        {{--                <h4>Event Creator</h4>--}}
        {{--                <form>--}}
        {{--                    <div class="row">--}}
        {{--                        <div class="col">--}}
        {{--                            <input type="text" class="form-control" v-model="form.channel" placeholder="Channel">--}}
        {{--                        </div>--}}
        {{--                        <div class="col">--}}
        {{--                            <input type="text" class="form-control" v-model="form.event" placeholder="Event">--}}
        {{--                        </div>--}}
        {{--                    </div>--}}
        {{--                    <div class="row mt-3">--}}
        {{--                        <div class="col">--}}
        {{--                            <div class="form-group">--}}
        {{--                                <textarea placeholder="Data" v-model="form.data" class="form-control" id="data"--}}
        {{--                                          rows="3"></textarea>--}}
        {{--                            </div>--}}
        {{--                        </div>--}}
        {{--                    </div>--}}
        {{--                    <div class="row text-right">--}}
        {{--                        <div class="col">--}}
        {{--                            <button type="submit" @click.prevent="sendEvent" class="btn btn-sm btn-primary">Send event--}}
        {{--                            </button>--}}
        {{--                        </div>--}}
        {{--                    </div>--}}
        {{--                </form>--}}
        {{--            </div>--}}
        {{--            <h4>Events</h4>--}}
        {{--            <table id="events" class="table table-striped table-hover">--}}
        {{--                <thead>--}}
        {{--                <tr>--}}
        {{--                    <th>Type</th>--}}
        {{--                    <th>Socket</th>--}}
        {{--                    <th>Details</th>--}}
        {{--                    <th>Time</th>--}}
        {{--                </tr>--}}
        {{--                </thead>--}}
        {{--                <tbody>--}}
        {{--                <tr v-for="log in logs.slice().reverse()">--}}
        {{--                    <td><span class="badge" :class="getBadgeClass(log)">@{{ log.type }}</span></td>--}}
        {{--                    <td>@{{ log.socketId }}</td>--}}
        {{--                    <td>@{{ log.details }}</td>--}}
        {{--                    <td>@{{ log.time }}</td>--}}
        {{--                </tr>--}}
        {{--                </tbody>--}}
        {{--            </table>--}}
        {{--        </div>--}}
    </div>
</div>
<script>
    new Vue({
        el: '#app',

        data: {
            mainModelClass: null,
            parentModelClass: null,
            modelClasses: {!! json_encode($models) !!},
            repository: null,
            relations: [],
            results: [],
            mainModelId: null,
            parentTargetModelId: null,
        },

        mounted() {
            this.mainModelClass = this.modelClasses[0] || null;
        },

        methods: {
            mainModelClassChange() {
                this.relations = []
                this.parentTargetModelId = null
                this.repository = null
            },
            isDisabled(config) {
                if (config.dependsOn === undefined) return false
                for (let dependsOnElement of config.dependsOn) {
                    if (this.childs.indexOf(dependsOnElement) < 1) return true
                }
                return false
            },

            sendDuplicateRequest() {
                $.post('{{ route( config('modelsManager.routeGroupPrefix').'.duplicate') }}', {
                    _token: '{{ csrf_token() }}',
                    mainModelClass: this.mainModelClass,
                    parentModelClass: this.parentModelClass,
                    mainModelId: this.mainModelId,
                    parentTargetModelId: this.parentTargetModelId,
                    relations: this.relations,
                }).fail(() => {
                    alert('Error sending event.');
                }).then((data) => {
                    this.results = data
                });
            },
            getModelAndRelations() {
                let url = `{{
                                route(
                                    config('modelsManager.routeGroupPrefix').'.getModelWithRelations',
                                    [":mainModelClassName",":mainModelId"]
                                )
                            }}`
                url = url.replace(":mainModelClassName", encodeURIComponent(this.mainModelClass));
                url = url.replace(":mainModelId", this.mainModelId);
                $.getJSON(url, (data) => {
                    this.repository = data['models']
                    this.relations = []
                    for (const modelClass in data['models']) {
                        if (data['models'][modelClass]['count'] > 0) this.relations.push(modelClass)
                    }
                })

            }
        }
    });
</script>
</body>
</html>
