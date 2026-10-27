using System.Windows;
using PubQuizApp.Models;
using PubQuizApp.ViewModels;

namespace PubQuizApp.Views
{
    public partial class QuizmasterWindow : Window
    {
        private readonly QuizSessionViewModel _viewModel;
        private PresentationWindow? _presentationWindow;

        public QuizmasterWindow()
        {
            InitializeComponent();
            _viewModel = new QuizSessionViewModel();
            DataContext = _viewModel;
        }

        private void OpenPresentationWindow_Click(object sender, RoutedEventArgs e)
        {
            if (_presentationWindow == null || !_presentationWindow.IsLoaded)
            {
                _presentationWindow = new PresentationWindow(_viewModel);
                _presentationWindow.Owner = this;
                _presentationWindow.Show();
            }
            else
            {
                _presentationWindow.Focus();
            }
        }

        private void AddPoints_Click(object sender, RoutedEventArgs e)
        {
            if (sender is FrameworkElement element && element.DataContext is TeamScore team)
            {
                team.Score += 10;
            }
        }

        private void MinusPoints_Click(object sender, RoutedEventArgs e)
        {
            if (sender is FrameworkElement element && element.DataContext is TeamScore team)
            {
                team.Score -= 10;
            }
        }

        private void Button_Click(object sender, RoutedEventArgs e)
        {

        }

        private void Button_Click_1(object sender, RoutedEventArgs e)
        {

        }

        private void Button_Click_2(object sender, RoutedEventArgs e)
        {

        }
    }
}
