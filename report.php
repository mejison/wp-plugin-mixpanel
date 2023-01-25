<link rel="stylesheet" href="/wp-content/plugins/mixpanel-plugin/bootstrap.min.css" />
<div id="dialog-report">
    <dialog :open="isOpen" class="dialog-report">
        <a href="#" @click="handleToggleDialog" class="close">&times;</a>
        <div class="container">
            <div class="metrics">
                <div class="total">
                    <h3>Total visitors</h3>
                    <div class="value">
                        {{ humanFormat(metrics.total) }}
                    </div>
                    <span>all time visitors</span>
                </div>
                <div class="today">
                    <h3>Visitors today</h3>
                    <div class="value">
                        {{ humanFormat(metrics.today) }}
                    </div>
                </div>
                <div class="average">
                    <h3>
                        Average daily visitors
                    </h3>
                    <div class="value">
                        {{ humanFormat(metrics.average_daily) }}
                    </div>
                </div>
            </div>
            <div class="graph">
                <bar-chart :bar-data="ChartConfig" :chart-options="options"></bar-chart>
            </div>
            <div class="tables">
                <div class="countries">
                    <datatable :columns="columnsCountries" :data="countries"></datatable>
                    <div class="lds-ring" v-if="isLoaded">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <datatable-pager v-model="pagination.countries.page" type="abbreviated"
                        :per-page="pagination.countries.per_page">
                    </datatable-pager>
                </div>
                <div class="sites">
                    <datatable :columns="columnsSites" :data="sites"></datatable>
                    <div class="lds-ring" v-if="isLoaded">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <datatable-pager v-model="pagination.sites.page" type="abbreviated"
                        :per-page="pagination.sites.per_page">
                    </datatable-pager>
                </div>
            </div>
        </div>
    </dialog>
</div>
<script src="https://cdn.jsdelivr.net/npm/vue@2.7.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuejs-datatable@1.7.0/dist/vuejs-datatable.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.min.js"></script>
<script src="https://unpkg.com/vue-chartjs@3.4.0/dist/vue-chartjs.js"></script>
<script src="https://unpkg.com/human-format@0.10/index.js"></script>
<script>
    Vue.component('bar-chart', {
        extends: VueChartJs.Bar,
        props: ['barData', 'chartOptions'],
        mounted() {
            this.renderChart(this.barData, this.chartOptions);
        },
    }, {
        responsive: true,
        maintainAspectRatio: false
    })

    var vueInstance;
    window.onload = function () {
        window.vueInstance = new Vue({
            el: '#dialog-report',

            data() {
                return {
                    isOpen: false,
                    humanFormat: humanFormat,
                    metrics: {
                        total: 0,
                        today: 0,
                        average_daily: 0,
                    },
                    ChartConfig: {
                        labels: [],
                        datasets: [{
                                data: [33],
                                backgroundColor: '#e34c3d',
                                borderColor: '#e34c3d',
                                label: "month1"
                            },
                            {
                                data: [22],
                                backgroundColor: '#e34c3d',
                                borderColor: '#e34c3d',
                                label: "month2"
                            },
                            {
                                data: [1],
                                backgroundColor: '#e34c3d',
                                borderColor: '#e34c3d',
                                label: "month3"
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                        },
                        hover: {
                            mode: 'nearest',
                            intersect: true
                        },
                        scales: {
                            xAxes: [{
                                display: true,
                                categoryPercentage: 0.5,
                                scaleLabel: {
                                    display: true,
                                    labelString: 'Month'
                                }
                            }],
                            yAxes: [{
                                display: true,
                                scaleLabel: {
                                    display: true,
                                    labelString: 'Visits'
                                }
                            }]
                        }
                    },
                    pagination: {
                        countries: {
                            page: 1,
                            per_page: 10,
                        },
                        sites: {
                            page: 1,
                            per_page: 10,
                        },
                    },
                    columnsCountries: [{
                            label: 'No.',
                            field: 'id',
                        },
                        {
                            label: 'Country',
                            representedAs: ({
                                    code,
                                }) =>
                                `<img width="40px" height="25px" crossorigin="anonymous" src="https://countryflagsapi.com/png/${code}" alt="country" />`,
                            interpolate: true
                        },
                        {
                            label: 'Country Name',
                            field: 'name',
                        },
                        {
                            label: 'Visitors',
                            field: 'visitors',
                        },
                    ],
                    columnsSites: [{
                            label: 'Site Name',
                            field: 'url',
                        },
                        {
                            label: 'Total Times',
                            field: 'count',
                        },
                    ],
                    countries: [],
                    sites: [],
                    currentPost: null,
                    isLoaded: false,
                }
            },

            mounted() {},

            methods: {
                formatDate(date, format) {
                    const map = {
                        mm: ("0" + (date.getMonth() + 1)).slice(-2),
                        dd: date.getDate(),
                        yyyy: date.getFullYear()
                    }

                    return format.replace(/mm|dd|yyyy/gi, matched => map[matched])
                },
                handleToggleDialog() {
                    this.isOpen = !this.isOpen;
                },
                calcMetrics(results) {
                    const dateToday = this.formatDate(new Date(), "yyyy-mm-dd");
                    const today = results[dateToday] ? results[dateToday] : 0;

                    results = Object.entries(results)
                    const total = results.reduce(function (acc, count) {
                        return acc + count[1];
                    }, 0)
                    this.metrics = {
                        total: total,
                        today: today,
                        average_daily: Math.ceil(today / 365)
                    }
                },
                openReport(data) {
                    const {
                        post,
                        stats
                    } = data;
                    this.currentPost = post;
                    if (stats.results) {
                        this.calcMetrics(stats.results);
                    }
                    this.handleToggleDialog();
                    this.loadTops(post);
                },
                loadTops(post) {
                    this.sites = []
                    this.countries = []
                    this.isLoaded = true;
                    fetch(`/wp-json/mixpanel/v1/visit?post_id=${post.post_id}`)
                        .then(e => e.json())
                        .then((data) => {
                            const {
                                countries,
                                sites
                            } = data;
                            Object.entries(sites).forEach((item, index) => {
                                this.sites = [
                                    ...this.sites,
                                    {
                                        id: index + 1,
                                        url: item[0],
                                        count: item[1],
                                    }
                                ]
                            })

                            Object.entries(countries).forEach((item, index) => {
                                this.countries = [
                                    ...this.countries,
                                    {
                                        id: index + 1,
                                        code: item[0],
                                        name: item[0],
                                        visitors: item[1]
                                    }
                                ]
                            });

                            this.sites = this.sites.sort(function (a, b) {
                                return a.count > b.count ? -1 : 1;
                            })
                            this.countries = this.countries.sort(function (a, b) {
                                return a.visitors > b.visitors ? -1 : 1;
                            })
                            this.isLoaded = false;
                        })
                }
            },
        })
    }
</script>
<style>
    .dialog-report {
        box-sizing: border-box;
        width: 100%;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        overflow: auto;
    }

    .dialog-report .close {
        font-size: 45px;
        position: absolute;
        z-index: 9999;
        top: 25px;
        right: 35px;
        color: #222;
        text-decoration: none;
    }

    .dialog-report .metrics {
        background: #2e3740;
        color: #fff;
        display: flex;
        justify-content: space-around;
        padding: 20px 0;
        border-radius: 6px;
    }

    .dialog-report .metrics h3 {
        font-size: 16px;
    }

    .dialog-report .metrics .value {
        font-size: 45px;
    }

    .dialog-report .metrics span {
        font-size: 14px;
    }

    .dialog-report .tables {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-gap: 20px;
        margin-top: 20px;
    }

    .lds-ring {
        display: inline-block;
        position: relative;
        width: 80px;
        height: 80px;
    }

    .lds-ring div {
        box-sizing: border-box;
        display: block;
        position: absolute;
        width: 45px;
        height: 45px;
        margin: 8px;
        border: 8px solid #ccc;
        border-radius: 50%;
        animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        border-color: #ccc transparent transparent transparent;
    }

    .lds-ring div:nth-child(1) {
        animation-delay: -0.45s;
    }

    .lds-ring div:nth-child(2) {
        animation-delay: -0.3s;
    }

    .lds-ring div:nth-child(3) {
        animation-delay: -0.15s;
    }

    @keyframes lds-ring {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>