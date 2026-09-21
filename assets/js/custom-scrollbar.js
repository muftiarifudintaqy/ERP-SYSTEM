/**
 * Custom Horizontal Scrollbar
 * Universal custom scrollbar untuk AG-Grid dan container lainnya
 *
 * Usage:
 * 1. Tambahkan attribute data-custom-scrollbar pada wrapper element
 * 2. Tambahkan element scrollbar setelah wrapper:
 *    <div class="custom-scrollbar">
 *      <div class="scrollbar-track">
 *        <div class="scrollbar-thumb"></div>
 *      </div>
 *    </div>
 * 3. Call: CustomScrollbar.init('[data-custom-scrollbar]')
 */

var CustomScrollbar = (function () {
  "use strict";

  var instances = [];

  function CustomScrollbarInstance(wrapper, scrollbarElement) {
    this.wrapper = wrapper;
    this.scrollbarElement = scrollbarElement;
    this.scrollbarThumb = scrollbarElement.querySelector(".scrollbar-thumb");
    this.scrollbarTrack = scrollbarElement.querySelector(".scrollbar-track");
    this.isDragging = false;
    this.startX = 0;
    this.startLeft = 0;
    this.viewport = null;

    this.init();
  }

  CustomScrollbarInstance.prototype.init = function () {
    var self = this;

    // Detect viewport element
    this.detectViewport();

    if (!this.viewport) {
      console.warn("Custom scrollbar: viewport not found");
      return;
    }

    // Bind events
    this.bindEvents();

    // Initial update
    setTimeout(function () {
      self.update();
    }, 100);

    // Update on window resize
    window.addEventListener("resize", function () {
      self.update();
    });
  };

  CustomScrollbarInstance.prototype.detectViewport = function () {
    // For AG-Grid
    var agViewport = this.wrapper.querySelector(
      ".ag-body-horizontal-scroll-viewport",
    );
    if (agViewport) {
      this.viewport = agViewport;
      this.isAgGrid = true;
      return;
    }

    // Fallback to center container for AG-Grid
    var agCenter = this.wrapper.querySelector(".ag-center-cols-viewport");
    if (agCenter) {
      this.viewport = agCenter;
      this.isAgGrid = true;
      return;
    }

    // For regular scrollable container
    if (this.wrapper.scrollWidth > this.wrapper.clientWidth) {
      this.viewport = this.wrapper;
      this.isAgGrid = false;
    }
  };

  CustomScrollbarInstance.prototype.update = function () {
    if (!this.viewport) return;

    var scrollWidth = this.viewport.scrollWidth;
    var clientWidth = this.viewport.clientWidth;

    if (scrollWidth <= clientWidth) {
      // No need for scrollbar
      this.scrollbarElement.classList.remove("active");
      return;
    }

    this.scrollbarElement.classList.add("active");

    // Calculate thumb width as percentage
    var thumbWidthPercent = (clientWidth / scrollWidth) * 100;
    this.scrollbarThumb.style.width = thumbWidthPercent + "%";

    // Calculate thumb position
    var scrollLeft = this.viewport.scrollLeft;
    var maxScroll = scrollWidth - clientWidth;
    var scrollPercent = (scrollLeft / maxScroll) * 100;
    var maxThumbLeft = 100 - thumbWidthPercent;
    var thumbLeft = (scrollPercent / 100) * maxThumbLeft;

    this.scrollbarThumb.style.left = thumbLeft + "%";
  };

  CustomScrollbarInstance.prototype.bindEvents = function () {
    var self = this;

    // Sync viewport scroll to scrollbar
    if (this.viewport) {
      this.viewport.addEventListener("scroll", function () {
        self.update();
      });
    }

    // Dragging scrollbar thumb
    this.scrollbarThumb.addEventListener("mousedown", function (e) {
      self.isDragging = true;
      self.startX = e.clientX;
      var thumbLeftPercent = parseFloat(self.scrollbarThumb.style.left) || 0;
      self.startLeft = thumbLeftPercent;
      e.preventDefault();
    });

    document.addEventListener("mousemove", function (e) {
      if (!self.isDragging) return;

      var trackWidth = self.scrollbarTrack.offsetWidth;
      var thumbWidth = self.scrollbarThumb.offsetWidth;

      var deltaX = e.clientX - self.startX;
      var deltaPercent = (deltaX / trackWidth) * 100;
      var newLeft = self.startLeft + deltaPercent;

      // Clamp
      var thumbWidthPercent = parseFloat(self.scrollbarThumb.style.width);
      var maxLeftPercent = 100 - thumbWidthPercent;
      newLeft = Math.max(0, Math.min(newLeft, maxLeftPercent));

      self.scrollbarThumb.style.left = newLeft + "%";

      // Sync to viewport
      var scrollPercent = (newLeft / maxLeftPercent) * 100;
      var scrollWidth = self.viewport.scrollWidth;
      var clientWidth = self.viewport.clientWidth;
      var maxScroll = scrollWidth - clientWidth;
      self.viewport.scrollLeft = (scrollPercent / 100) * maxScroll;
    });

    document.addEventListener("mouseup", function () {
      self.isDragging = false;
    });

    // Click on track to jump
    this.scrollbarTrack.addEventListener("click", function (e) {
      if (e.target === self.scrollbarThumb) return;

      var trackRect = self.scrollbarTrack.getBoundingClientRect();
      var clickX = e.clientX - trackRect.left;
      var trackWidth = trackRect.width;
      var thumbWidth = self.scrollbarThumb.offsetWidth;

      var newThumbLeft = clickX - thumbWidth / 2;
      newThumbLeft = Math.max(
        0,
        Math.min(newThumbLeft, trackWidth - thumbWidth),
      );

      var newLeftPercent = (newThumbLeft / trackWidth) * 100;
      var thumbWidthPercent = parseFloat(self.scrollbarThumb.style.width);
      var maxLeftPercent = 100 - thumbWidthPercent;
      var clampedLeft = Math.max(0, Math.min(newLeftPercent, maxLeftPercent));

      self.scrollbarThumb.style.left = clampedLeft + "%";

      // Sync to viewport
      var scrollPercent = (clampedLeft / maxLeftPercent) * 100;
      var scrollWidth = self.viewport.scrollWidth;
      var clientWidth = self.viewport.clientWidth;
      var maxScroll = scrollWidth - clientWidth;
      self.viewport.scrollLeft = (scrollPercent / 100) * maxScroll;
    });
  };

  // Public API
  return {
    init: function (selector) {
      var wrappers = document.querySelectorAll(
        selector || "[data-custom-scrollbar]",
      );

      wrappers.forEach(function (wrapper) {
        // Find or create scrollbar element
        var scrollbarElement = wrapper.nextElementSibling;

        if (
          !scrollbarElement ||
          !scrollbarElement.classList.contains("custom-scrollbar")
        ) {
          // Create scrollbar element
          scrollbarElement = document.createElement("div");
          scrollbarElement.className = "custom-scrollbar";
          scrollbarElement.innerHTML =
            '<div class="scrollbar-track"><div class="scrollbar-thumb"></div></div>';

          // Insert after wrapper
          wrapper.parentNode.insertBefore(
            scrollbarElement,
            wrapper.nextSibling,
          );
        }

        var instance = new CustomScrollbarInstance(wrapper, scrollbarElement);
        instances.push(instance);
      });

      return instances;
    },

    update: function () {
      instances.forEach(function (instance) {
        instance.update();
      });
    },

    getInstance: function (index) {
      return instances[index] || null;
    },

    getInstances: function () {
      return instances;
    },
  };
})();

// Auto initialize on DOM ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", function () {
    CustomScrollbar.init();
  });
} else {
  CustomScrollbar.init();
}
