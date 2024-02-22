<template>
  <div class="mt-3x -ml-8x">
    <div class="w-full relative z-20" style="box-shadow: 0 5px 3px rgba(0, 0, 0, 0.15);">sdff</div>

    <div
      class="flex w-full overflow-hidden relative"
      style="height: 500px; background: linear-gradient(to right, rgba(117,169,157,0.5) 0%, rgba(117,169,157,0.5) 50%, rgba(187,57,33,0.5) 50%, rgba(187,57,33,0.5) 100%);"
    >
      <div class="w-1/3 transform rotate-12 -mt-6x relative z-20" style="height: 1000px;">
        <div class="transform -rotate-12 text-center mt-8x ml-6x pt-8x text-white" style="height: 500px">
          <h3 class="text-2x mb-md">Client</h3>
          <i class="fa fa-laptop text-white-50 text-3x" style="font-size: 100px" />

          <i class="fa fa-arrows-alt-h absolute text-white text-3x" style="right: -1.3rem; top: calc(50% - 3rem); filter:drop-shadow(2px -3px 1px rgba(0, 0, 0, 0.1)" />
          <i class="fa fa-cloud absolute text-white text-2x" style="right: -1.1rem; top: calc(50% - 5rem); filter:drop-shadow(2px -3px 1px rgba(0, 0, 0, 0.1)" />
        </div>
      </div>

      <div class="w-1/3 transform rotate-12 -mt-6x z-10" style="height: 1000px; background: #88ADBA">
        <div class="transform -rotate-12 text-center mt-8x ml-6x pt-8x text-white" style="height: 500px">
          <h3 class="text-2x">Docker Swarm</h3>
          <h3 class="-mt-xs text-white-50 text-xl">(Server Environment)</h3>

          <div class="flex mt-xl text-left">
            <div class="rounded-base w-1/2">
              <h4 class="text-xl">Frontend</h4>
              <ul>
                <li>Vue.js <span class="text-white-50 text-mmd">(Single page app)</span></li>
                <li>Typescript <span class="text-white-50 text-mmd">(Static type checking)</span></li>
                <li>Tailwind <span class="text-white-50 text-mmd">(Utility-driven styling)</span></li>
                <li>Font Awesome <span class="text-white-50 text-mmd">(Scalable icons)</span></li>
              </ul>
            </div>

            <div class="rounded-base x w-1/2">
              <h4 class="text-xl">Backend</h4>
              <ul>
                <li>PHP Core</li>
                <li>PHP Elasticsearch API</li>
              </ul>
            </div>
          </div>

          <div class="absolute text-white-50 text-mmd" style="top: -1.2rem; right: -2rem">(Hosted by D4Science)</div>

          <i class="fa fa-arrows-alt-h absolute text-white text-3x" style="right: -2.3rem; top: calc(50% - 3rem); filter:drop-shadow(2px -3px 1px rgba(0, 0, 0, 0.1)" />
          <i class="fa fa-lock-open absolute text-white text-2x" style="right: -2.2rem; top: calc(50% - 5rem); filter:drop-shadow(2px -3px 1px rgba(0, 0, 0, 0.1)" />
        </div>
      </div>

      <div class="transform rotate-12 w-1/3 -mt-6x" style="height: 1000px;">
        <div class="transform -rotate-12 text-center mt-8x ml-6x pt-8x text-white" style="height: 500px">
          <div style="margin-left: -90px">
            <h3 class="text-2x mb-md">OpenSearch</h3>
            <i class="fa fa-database text-white-50 text-3x" style="font-size: 100px" />
          </div>

          <div class="absolute text-white-50 text-mmd" style="top: -1.2rem; right: 4rem">(Hosted by D4Science)</div>
        </div>
      </div>
    </div>

    <ul
      class="flex bg-yellow-50 py-base px-2x justify-end text-black-50 text-mmd relative z-20"
      style="box-shadow: 0px -4px 3px rgba(0, 0, 0, 0.15);"
    >
      <li class="">
        Docker Hub Repository:

        <strong>
          <a target="_blank" href="https://hub.docker.com/repository/docker/ariadneplusportal/www-portal-staging">
            https://hub.docker.com/repository/docker/ariadneplusportal/www-portal-staging
          </a>
        </strong>
      </li>

      <li class="pl-lg">
        Public GIT Repositor:

        <strong>
          <a target="_blank" href="https://github.com/ARIADNE-Infrastructure/portal">
            https://github.com/ARIADNE-Infrastructure/portal
          </a>
        </strong>
      </li>
    </ul>
  </div>
</template>
