export default {
  removeEmptyAttribute: function (obj) {
    return Object.keys(obj)
      .filter((key) => obj[key] !== null)
      .reduce((acc, key) => {
        acc[key] = obj[key]
        return acc
      }, {})
  },
  setNullAttribute: function (obj, except = 'none') {
    return Object.keys(obj).forEach((i) => {
      if (obj[i] !== except) {
        obj[i] = null
      }
    })
  },
}
