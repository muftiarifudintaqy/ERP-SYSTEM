// Reusable AG Grid components
class AgGridHelper {
  constructor(gridId) {
    this.gridId = gridId;
    this.valueFilters = Object.create(null);
    this.distinctCache = Object.create(null);
  }

  // Your reusable helper methods here
  formatRupiah(n) {
    if (n == null) return "-";
    try {
      return (+n).toLocaleString("id-ID");
    } catch {
      return "Rp " + n;
    }
  }

  buildFooterRowFromTotals(totals, gridOptions) {
    // Implementation here
  }

  // More reusable methods...
}

// Custom header component
class CustomHeaderWithFilter {
  init(params) {
    // Your header with filter implementation
    // Make sure to use this.gridId for uniqueness
  }

  // Other methods...
}
