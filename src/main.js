
import Vue from 'vue'
import App from './App.vue'
import VueRouter from 'vue-router'
import Explore from './components/Explore.vue'
import { generateUrl } from '@nextcloud/router'
import Vuex, { Store } from 'vuex'
import axios from '@nextcloud/axios'

import { Tooltip } from '@nextcloud/vue'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA

Vue.use(Vuex)
Vue.use(VueRouter)

Vue.directive('tooltip', Tooltip)

const feedUrl = generateUrl('/apps/news/feeds')
const folderUrl = generateUrl('/apps/news/folders')

const routes = [
	{
		name: 'explore',
		path: '#explore',
		component: Explore,
	},
]

const router = new VueRouter({
	mode: 'history',
	base: generateUrl('apps/news'),
	routes,
})

const store = new Store({
	state: {
		folders: [],
		feeds: [],
	},
	mutations: {
		addFolders(state, folders) {
			folders.forEach((it) => {
				it.feedCount = 0
				state.folders.push(it)
			})
		},
		addFeeds(state, feeds) {
			feeds.forEach((it) => {
				state.feeds.push(it)
				const folder = state.folders.find(
					(folder) => folder.id === it.folderId,
				)
				if (folder) {
					folder.feeds.push(it)
					folder.feedCount += it.unreadCount
				}
			})
		},
	},
	actions: {
		addFolder({ commit }, { folder }) {
			axios
				.post(folderUrl, { folderName: folder.name })
				.then((response) =>
					commit('addFolders', response.data.folders),
				)
		},
		deleteFolder({ commit }, { folder }) {
			/**
            this.getByFolderId(folderId).forEach(function (feed) {
                promises.push(self.reversiblyDelete(feed.id, false, true));
            });
            this.updateUnreadCache();
			 */
			axios.delete(folderUrl + '/' + folder.id).then(() => {
				commit('deleteFolder', folder.id)
			})
		},
		loadFolder({ commit }) {
			axios.get(folderUrl).then((response) => {
				commit('addFolders', response.data.folders)
				axios
					.get(feedUrl)
					.then((response) =>
						commit('addFeeds', response.data.feeds),
					)
			})
		},
		addFeed({ commit }, { feedReq }) {
			let url = feedReq.url.trim()
			if (!url.startsWith('http')) {
				url = 'https://' + url
			}

			/**
            if (title !== undefined) {
                title = title.trim();
            }
			 */

			const feed = {
				url,
				folderId: feedReq.folder.id || 0,
				title: null,
				unreadCount: 0,
			}

			// this.add(feed);
			// this.updateFolderCache();

			axios
				.post(feedUrl, {
					url: feed.url,
					parentFolderId: feed.folderId,
					title: null,
					user: null,
					password: null,
					fullDiscover: feed.autoDiscover,
				})
				.then(() => {
					commit('addFeed', feed)
				})
		},
	},
})

export default new Vue({
	router,
	store,
	el: '#content',
	render: (h) => h(App),
})