<template>
  <div class="m-site-footer">
    <div class="main-footer max-w-screen-xl mx-auto">
      <div class="logos ml-base">
        <ul>
          <li>
            <a href="https://www.york.ac.uk/">
              <img src="https://archaeologydataservice.ac.uk/app/uploads/2022/06/logo-uoy.svg"
              style="width: 150px"
              alt="University of York logo"/>
            </a>
          </li>
          <li>
            <a href="http://doi.org/10.17616/R3MW23">
              <img src="https://archaeologydataservice.ac.uk/app/uploads/2022/06/logo-re3data-color.svg"
              style="width: 198px"
              alt="re3data.org logo" />
            </a>
          </li>
          <li>
            <a href="https://www.coretrustseal.org/why-certification/certified-repositories/">
              <img src="https://archaeologydataservice.ac.uk/app/uploads/2022/06/logo-core-trust-seal-color.png"
              alt="CoreTrustSeal logo" />
            </a>
          </li>
          <li>
            <a href="https://www.worlddatasystem.org/">
              <img src="https://archaeologydataservice.ac.uk/app/uploads/2022/06/logo-wds-color.jpg"
              alt="World Data System logo" />
            </a>
          </li>
        </ul>
      </div>
      <div class="social ml-base">
        <h3>Follow us</h3>
        <ul>
          <li><a href="https://www.facebook.com/archaeology.data.service/"><i class="fa-brands fa-facebook"></i></a></li>
          <li><a href="https://twitter.com/ADS_Update"><i class="fa-brands fa-twitter"></i></a></li>
          <li><a href="https://www.instagram.com/archaeologydataservice/?hl=en"><i class="fa-brands fa-instagram"></i></a>
          </li>
          <li><a href="https://www.linkedin.com/company/archaeology-data-service/"><i
                class="fa-brands fa-linkedin"></i></a></li>
        </ul>
      </div>
    </div>

    <div class="footer-navigation">

      <div class="max-w-screen-xl mx-auto">

        <nav aria-label="Footer navigation" >
          <div class="menu-footer-navigation-container">
            <ul id="menu-footer-navigation" class="menu">
              <li id="menu-item-6820" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-6820"><a
                  href="https://www.york.ac.uk/about/legal-statements/">University of York legal statements</a></li>
              <li id="menu-item-3156" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-3156"><a
                  href="https://archaeologydataservice.ac.uk/terms-and-conditions/">Website terms and conditions</a></li>
              <li id="menu-item-5469" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-5469"><a
                  href="https://archaeologydataservice.ac.uk/cookie-policy/">Cookie policy</a></li>
              <li id="menu-item-3157" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-3157"><a
                  href="https://archaeologydataservice.ac.uk/privacy/">Privacy policy</a></li>
              <li id="menu-item-3373" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-3373"><a
                  href="https://archaeologydataservice.ac.uk/accessibility/">Accessibility</a></li>
              <li id="menu-item-3951" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-3951"><a
                  href="https://archaeologydataservice.ac.uk/contact/">Contact</a></li>
            </ul>
          </div>
        </nav>

        <div class="legal">
          &copy; Archaeology Data Service <span id="footer-year"></span>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const assets: string = $computed(() => generalModule.getAssetsDir);
</script>
