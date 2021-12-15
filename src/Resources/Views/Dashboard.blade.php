<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <title>Model Dumper Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script
        src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>
{{--    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.min.js"></script>--}}
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>


</head>

<body>
<div class="container" id="app">
    <div class="card col-xs-12 mt-4">
        <div class="card-header">
            <form id="duplicate" class="form-inline" role="form">
                <label class="my-1 mr-2" for="model">Parent Model:</label>
                <select class="form-control form-control-sm mr-2" @change="modelChange()" name="model" id="model" v-model="model">
                    <option v-for="(parentConfig,parentModel) in models" :value="parentConfig">@{{ parentModel}}</option>
                </select>
                <div v-if="model">
                    <div v-for="(hasOneConfig,hasOneModel) in model.hasOne" >
                        <input type="checkbox"  :id="hasOneConfig" :value="hasOneModel" v-model="childs">
                        @{{ hasOneModel }}<br/>
                    </div>
                    <div v-for="(hasManyConfig,hasManyModel) in model.hasMany" >
                        <input type="checkbox" :disabled="isDisabled(hasManyConfig)" :id="hasManyConfig" :value="hasManyModel" v-model="childs">
                        @{{ hasManyModel }}<br/>
                    </div>
                </div>
                <div v-if="childs.length">
                    <label class="my-1 mr-2" for="duplicateTo">Duplicate For Same Parent Model with  Id :</label>
                    <input class="form-control form-control-sm mr-2" v-model="duplicateTo" placeholder="database main id">
                    <button v-if="duplicateTo" type="submit" @click.prevent="sendEvent" class="mr-2 btn btn-sm btn-primary">
                        Duplicate
                    </button>
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
            model: null,
            models: {!! json_encode($models) !!},
            childs:[],
            duplicateTo:null,
            form: {
                channel: null,
                event: null,
                data: null
            },
            logs: [],
        },

        mounted() {
            this.model = this.models[0] || null;
        },

        methods: {
            modelChange() {
                this.childs=[]
                this.duplicateTo=null
            },
            isDisabled(config){
                if (config.dependsOn===undefined) return false
                for (let dependsOnElement of config.dependsOn) {
                    if (this.childs.indexOf(dependsOnElement)<1) return true
                }
                return false
            },
            loadChart() {
                $.getJSON('{{ url(request()->path().'/api') }}/' + this.app.id + '/statistics', (data) => {

                    let chartData = [
                        {
                            x: data.peak_connections.x,
                            y: data.peak_connections.y,
                            type: 'lines',
                            name: '# Peak Connections'
                        },
                        {
                            x: data.websocket_message_count.x,
                            y: data.websocket_message_count.y,
                            type: 'bar',
                            name: '# Websocket Messages'
                        },
                        {
                            x: data.api_message_count.x,
                            y: data.api_message_count.y,
                            type: 'bar',
                            name: '# API Messages'
                        }
                    ];
                    let layout = {
                        margin: {
                            l: 50,
                            r: 0,
                            b: 50,
                            t: 50,
                            pad: 4
                        }
                    };

                    // this.chart = Plotly.newPlot('statisticsChart', chartData, layout);
                });
            },


            sendEvent() {
                $.post('{{ url(request()->path().'/event') }}', {
                    _token: '{{ csrf_token() }}',
                    key: this.app.key,
                    secret: this.app.secret,
                    appId: this.app.id,
                    channel: this.form.channel,
                    event: this.form.event,
                    data: this.form.data,
                }).fail(() => {
                    alert('Error sending event.');
                });
            }
        }
    });
</script>
</body>
</html>
