<link rel="stylesheet" href="/wp-content/plugins/mixpanel-plugin/bootstrap.min.css" />
<div id="dialog-report">
    <dialog :open="isOpen" class="dialog-report">
        <div>
            <a href="#" @click="handleToggleDialog" class="close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="25px" height="25px"><path d="M360 224L272 224v-56c0-9.531-5.656-18.16-14.38-22C248.9 142.2 238.7 143.9 231.7 150.4l-96 88.75C130.8 243.7 128 250.1 128 256.8c.3125 7.781 2.875 13.25 7.844 17.75l96 87.25c7.031 6.406 17.19 8.031 25.88 4.188s14.28-12.44 14.28-21.94l-.002-56L360 288C373.3 288 384 277.3 384 264v-16C384 234.8 373.3 224 360 224zM256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM256 464c-114.7 0-208-93.31-208-208S141.3 48 256 48s208 93.31 208 208S370.7 464 256 464z"/></svg>
                &nbsp;Back to Dashboard
            </a>
        </div>
        <div class="container">
            <div class="info">
                <div class="logo">
                    <img src="https://realtywire.com/wp-content/uploads/2022/05/new-realty-wire-logo-v20.jpg" alt="logo" />
                </div>
                <h4 class="title">News Article Traffic Report</h4>
                <ul>
                    <li>
                        <b>News article: </b> {{ currentPost?.post_title }}
                    </li>
                    <li>
                        <b>Distribution date: </b> {{ currentPost?.post_date }}
                    </li>
                </ul>
                <p>
                    See the clipping report to see a list of sites that posted your news article <a href="https://realtywire.com/results-report" target="blank">CLICK HERE</a>
                </p>
                <b>
                    This report shows you the number of times your news article has been viewed:
                </b>
            </div>
            <div class="metrics">
                <div class="total">
                    <h3>Total views</h3>
                    <div class="value">
                        {{ humanFormat(metrics.total) }}
                    </div>
                    <span>all time views</span>
                </div>
                <div class="today">
                    <h3>Views today</h3>
                    <div class="value">
                        {{ humanFormat(metrics.today) }}
                    </div>
                </div>
                <div class="average">
                    <h3>
                        Average daily views
                    </h3>
                    <div class="value">
                        {{ humanFormat(metrics.average_daily) }}
                    </div>
                </div>
            </div>
            <div class="graph">
                <bar-chart v-if=" ! isLoaded" :bar-data="ChartConfig" :chart-options="options"></bar-chart>
                <div class="lds-ring" v-if="isLoaded">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
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
                    <!-- <datatable-pager v-if=" ! isLoaded" v-model="pagination.countries.page" type="abbreviated"
                        :per-page="pagination.countries.per_page">
                    </datatable-pager> -->
                </div>
                <div class="sites">
                    <datatable :columns="columnsSites" :data="sites"></datatable>
                    <div class="lds-ring" v-if="isLoaded">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <datatable-pager v-if=" ! isLoaded" v-model="pagination.sites.page" type="abbreviated"
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
    });

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
                        datasets: []
                    },
                    options: {
                        responsive: true,
                        height: '400px',
                        width: '700px',
                        maintainAspectRatio: false,
                        hover: {
                            mode: 'nearest',
                            intersect: true
                        },
                        scales: {
                            xAxes: [{
                                display: true,
                                categoryPercentage: 1,
                            }],
                            yAxes: [{
                                    ticks: {
                                        display: false //this will remove only the label
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
                            label: 'Views',
                            field: 'visitors',
                        },
                    ],
                    columnsSites: [{
                            label: 'Site Name',
                            field: 'url',
                        },
                        {
                            label: 'Views',
                            field: 'count',
                        },
                    ],
                    countries: [],
                    sites: [],
                    currentPost: {},
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
                    this.handleToggleDialog();
                    this.loadTops(post);
                },
                loadTops(post) {
                    this.sites = []
                    this.countries = []
                    this.ChartConfig = {
                        labels: [],
                        datasets: [],
                    };
                    this.metrics = {
                        total: 0,
                        today: 0,
                        average_daily: 0,
                    };
                    this.isLoaded = true;
                    fetch(`/wp-json/mixpanel/v1/visit?post_id=${post.post_id}&post_title=${post.post_title}`)
                        .then(e => e.json())
                        .then((data) => {
                            const {
                                countries,
                                sites,
                                graph,
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

                            const {
                                results
                            } = graph;
                            const labels = Object.keys(results);
                            const datasets = Object.values(results);

                            this.calcMetrics(results);

                            this.ChartConfig = {
                                labels: [...labels],
                                datasets: [{
                                    data: [...datasets],
                                    backgroundColor: "#e34c3d",
                                    label: "Views"
                                }]
                            }
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
        z-index: 999;
        top: 0;
        left: 0;
        overflow: auto;
    }

    .dialog-report .close {
        font-size: 20px;
        color: #222;
        text-decoration: none;
        display: flex;
        align-items: center;
        margin: 20px 0;
    }

    .info .logo {
        width: 250px;
    }

    .info .logo img {
        object-fit: contain;
        width: 100%;
    }

    .info .title {
        font-size: 20px;
        margin: 0 0 20px 0;
    }

    .graph {
        min-height: 400px;
        width: 100%;
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